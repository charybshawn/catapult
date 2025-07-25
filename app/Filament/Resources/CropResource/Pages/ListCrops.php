<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Filament\Tables\Table;

class ListCrops extends ListRecords
{
    protected static string $resource = CropResource::class;

    // Set default sort for the page
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'planting_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    public function mount(): void
    {
        parent::mount();
        
        // Clear previous query log
        DB::flushQueryLog();
        
        // Enable query logging for debugging
        DB::enableQueryLog();
    }
    
    protected function getTableRecordsPerPageSelectOptions(): array 
    {
        return [10, 25, 50, 100];
    }
    
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();
        
        // Add additional logging for debugging
        Log::info('Recipe and Seed Entry Data:', [
            'recipes' => \App\Models\Recipe::with('seedEntry')->get()->map(function($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'seed_entry_id' => $recipe->seed_entry_id,
                    'seed_entry_name' => $recipe->seedEntry ? ($recipe->seedEntry->common_name . ' - ' . $recipe->seedEntry->cultivar_name) : null,
                ];
            })
        ]);
        
        // Override default ordering to prevent ONLY_FULL_GROUP_BY errors
        // Force ordering by a column that's part of the GROUP BY
        $query->reorder('crops.planting_at', 'desc');
        
        return $query->with(['recipe.seedEntry']);
    }
    
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();
        
        // Log the queries for debugging
        $queries = DB::getQueryLog();
        if (!empty($queries)) {
            Log::info('Grows List Query:', [
                'queries' => $queries,
                'sort' => $this->getTableSortColumn(),
                'direction' => $this->getTableSortDirection(),
            ]);
        }
        
        return $records;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('debug')
                ->label('Debug Data')
                ->icon('heroicon-o-bug-ant')
                ->color('gray')
                ->action(function () {
                    $query = parent::getTableQuery();
                    
                    // Get the SQL query with bindings
                    $sql = $query->toSql();
                    $bindings = $query->getBindings();
                    
                    // Get a sample of records
                    $records = $query->limit(5)->get()->toArray();
                    
                    // Log everything
                    \Illuminate\Support\Facades\Log::debug('Debug data', [
                        'sql' => $sql,
                        'bindings' => $bindings,
                        'records' => $records,
                    ]);
                    
                    return view('filament.resources.crop-resource.debug', [
                        'query' => [
                            'sql' => $sql,
                            'bindings' => $bindings,
                        ],
                        'records' => $records,
                    ]);
                }),
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->tooltip('Create Grow Batch'),
        ];
    }
    
    public function getTableRefreshInterval(): ?string
    {
        // Refresh the table every 5 minutes to update time values
        return '5m';
    }
} 