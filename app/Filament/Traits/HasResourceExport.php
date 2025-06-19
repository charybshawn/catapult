<?php

namespace App\Filament\Traits;

use App\Services\ImportExport\ResourceExportService;
use App\Models\DataExport;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

trait HasResourceExport
{
    /**
     * Get the export action for the resource
     */
    public static function getExportAction(): Action
    {
        $resourceName = static::getResourceName();
        
        return Action::make('export')
            ->label('Export ' . ucfirst($resourceName))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->form([
                Forms\Components\Select::make('format')
                    ->label('Export Format')
                    ->options([
                        'json' => 'JSON',
                        'csv' => 'CSV',
                    ])
                    ->default('json')
                    ->required(),
                    
                Forms\Components\Toggle::make('include_timestamps')
                    ->label('Include Timestamps')
                    ->helperText('Include created_at and updated_at columns')
                    ->default(false),
            ])
            ->action(function (array $data) use ($resourceName) {
                try {
                    $exportService = new ResourceExportService();
                    
                    $options = [
                        'format' => $data['format'],
                        'include_timestamps' => $data['include_timestamps'],
                    ];
                    
                    $zipPath = $exportService->exportResource($resourceName, $options);
                    
                    // Read manifest for statistics
                    $zip = new \ZipArchive();
                    $zip->open($zipPath);
                    $manifestContent = $zip->getFromName('manifest.json');
                    $manifest = json_decode($manifestContent, true);
                    $zip->close();
                    
                    // Save export record
                    $export = DataExport::create([
                        'resource' => $resourceName,
                        'filename' => basename($zipPath),
                        'filepath' => $zipPath,
                        'format' => $data['format'],
                        'manifest' => $manifest,
                        'options' => $options,
                        'file_size' => filesize($zipPath),
                        'record_count' => array_sum($manifest['statistics'] ?? []),
                        'user_id' => auth()->id(),
                    ]);
                    
                    Notification::make()
                        ->title('Export Successful')
                        ->body("Exported " . number_format($export->total_records) . " records")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('download')
                                ->label('Download')
                                ->url(route('filament.admin.data-export.download', $export))
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                        
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Export Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
    
    /**
     * Get the resource name for export/import
     */
    protected static function getResourceName(): string
    {
        // Extract resource name from class name
        $className = class_basename(static::class);
        $resourceName = str_replace('Resource', '', $className);
        return strtolower($resourceName);
    }
}