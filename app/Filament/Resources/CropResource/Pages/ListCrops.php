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
    protected static string $view = 'filament.resources.crop-resource.pages.list-crops';

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
            'recipes' => \App\Models\Recipe::with('masterSeedCatalog', 'masterCultivar')->get()->map(function($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'common_name' => $recipe->common_name,
                    'cultivar_name' => $recipe->cultivar_name,
                ];
            })
        ]);
        
        // Override default ordering to prevent ONLY_FULL_GROUP_BY errors
        // Force ordering by a column that's part of the GROUP BY
        $query->reorder('crops.planting_at', 'desc');
        
        return $query->with(['recipe.masterSeedCatalog', 'recipe.masterCultivar']);
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
            Actions\CreateAction::make(),
            ...parent::getHeaderActions(),
        ];
    }
    
    public function getTableRefreshInterval(): ?string
    {
        // Refresh the table every 5 minutes to update time values
        return '5m';
    }
} 