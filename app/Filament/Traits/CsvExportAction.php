<?php

namespace App\Filament\Traits;

use App\Services\CsvExportService;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait CsvExportAction
{
    /**
     * Get the CSV export action for table header actions
     */
    public static function getCsvExportAction(): Action
    {
        return Action::make('exportCsv')
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->form([
                TextInput::make('filename')
                    ->label('Filename (optional)')
                    ->placeholder('Leave empty for auto-generated name')
                    ->helperText('Will automatically add .csv extension'),
                    
                CheckboxList::make('columns')
                    ->label('Columns to Export')
                    ->options(static::getCsvExportColumns())
                    ->default(array_keys(static::getCsvExportColumns()))
                    ->required()
                    ->columns(2)
                    ->helperText('Select which columns to include in the export'),
                    
                Checkbox::make('include_relationships')
                    ->label('Include Relationship Data')
                    ->helperText('Include related model data (may slow down export for large datasets)')
                    ->default(false),
            ])
            ->action(function (array $data, $livewire) {
                try {
                    // Get the table query
                    $query = static::getTableQuery();
                    
                    // Apply current table filters if available
                    if (method_exists($livewire, 'getFilteredTableQuery')) {
                        $query = $livewire->getFilteredTableQuery();
                    } elseif (method_exists($livewire, 'getTableQuery')) {
                        $query = $livewire->getTableQuery();
                    }
                    
                    // Include relationships if requested
                    if ($data['include_relationships'] && !empty(static::getCsvExportRelationships())) {
                        $relationships = static::getCsvExportRelationships();
                        $query = $query->with($relationships);
                    }
                    
                    // Export the data
                    $csvService = new CsvExportService();
                    $filename = $csvService->export(
                        $query,
                        $data['columns'],
                        $data['filename'] ?: null
                    );
                    
                    // Provide download response
                    $filePath = $csvService->getFilePath($filename);
                    
                    Notification::make()
                        ->success()
                        ->title('CSV Export Successful')
                        ->body("Exported {$query->count()} records to {$filename}")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('download')
                                ->label('Download')
                                ->url(route('csv.download', ['filename' => $filename]))
                                ->openUrlInNewTab()
                        ])
                        ->persistent()
                        ->send();
                        
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Export Failed')
                        ->body($e->getMessage())
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Export to CSV')
            ->modalDescription('Configure your CSV export options below.')
            ->modalSubmitActionLabel('Export');
    }
    
    /**
     * Get the query for the table - override in resources if needed
     */
    protected static function getTableQuery(): Builder
    {
        return static::getModel()::query();
    }
    
    /**
     * Define which columns are available for CSV export
     * Override this method in your resource to customize available columns
     */
    protected static function getCsvExportColumns(): array
    {
        // Use database schema inspection to get all available columns
        return static::getColumnsFromSchema();
    }
    
    /**
     * Get columns from database schema as fallback
     */
    protected static function getColumnsFromSchema(): array
    {
        $model = new (static::getModel());
        $schemaColumns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
        
        // Filter out sensitive columns
        $excludeColumns = ['password', 'remember_token'];
        $columns = [];
        
        foreach ($schemaColumns as $column) {
            if (!in_array($column, $excludeColumns)) {
                $columns[$column] = static::formatColumnLabel($column);
            }
        }
        
        return $columns;
    }
    
    /**
     * Format column name into a readable label
     */
    protected static function formatColumnLabel(string $columnName): string
    {
        // Handle relationship notation
        if (str_contains($columnName, '.')) {
            $parts = explode('.', $columnName);
            $formatted = [];
            foreach ($parts as $part) {
                $formatted[] = Str::title(str_replace('_', ' ', $part));
            }
            return implode(' → ', $formatted);
        }
        
        return Str::title(str_replace('_', ' ', $columnName));
    }
    
    /**
     * Define which relationships to include when exporting
     * Override this method in your resource to define relationships
     * 
     * Example:
     * return ['category', 'user.profile', 'tags'];
     */
    protected static function getCsvExportRelationships(): array
    {
        return [];
    }
    
    /**
     * Add relationship columns to export options
     * Call this method in getCsvExportColumns() to include relationship data
     */
    protected static function addRelationshipColumns(array $baseColumns, array $relationships): array
    {
        $columns = $baseColumns;
        
        foreach ($relationships as $relationship => $fields) {
            if (is_numeric($relationship)) {
                // Simple relationship name provided
                $relationshipName = $fields;
                $relationshipFields = ['id', 'name', 'title'];
            } else {
                // Relationship with specific fields
                $relationshipName = $relationship;
                $relationshipFields = is_array($fields) ? $fields : [$fields];
            }
            
            foreach ($relationshipFields as $field) {
                $key = "{$relationshipName}.{$field}";
                $label = Str::title(str_replace(['_', '.'], ' ', $key));
                $columns[$key] = $label;
            }
        }
        
        return $columns;
    }
}