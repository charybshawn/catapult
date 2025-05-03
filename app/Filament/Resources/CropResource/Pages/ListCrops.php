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
        return 'time_to_next_stage';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }

    public function mount(): void
    {
        parent::mount();
        
        // Clear previous query log
        DB::flushQueryLog();
        
        // Enable query logging for debuggings
        DB::enableQueryLog();
    }
    
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();
        
        // Log the queries for debugging
        $queries = DB::getQueryLog();
        if (!empty($queries)) {
            Log::info('CropResource List Query:', [
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
        ];
    }
} 