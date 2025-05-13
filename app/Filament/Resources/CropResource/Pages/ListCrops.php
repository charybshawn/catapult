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

class ListCrops extends ListRecords
{
    protected static string $resource = CropResource::class;

    // Set default sort for the page
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'planted_at';
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
        Log::info('Recipe and Variety Data:', [
            'recipes' => \App\Models\Recipe::with('seedVariety')->get()->map(function($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'seed_variety_id' => $recipe->seed_variety_id,
                    'variety_name' => $recipe->seedVariety ? $recipe->seedVariety->name : null,
                ];
            })
        ]);
        
        // Override default ordering to prevent ONLY_FULL_GROUP_BY errors
        // By default, Filament will order by 'id' which isn't in the GROUP BY clause
        $sortColumn = $this->getTableSortColumn();
        if (!$sortColumn) {
            // Force ordering by a column that's part of the GROUP BY
            $query->reorder('planted_at', 'desc');
        }
        
        return $query->with(['recipe.seedVariety']);
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
} 