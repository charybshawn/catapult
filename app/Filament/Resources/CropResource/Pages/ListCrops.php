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
use App\Models\Crop;
use Carbon\Carbon;
use App\Services\CropStageCache;

class ListCrops extends ListRecords
{
    protected static string $resource = CropResource::class;
    protected static string $view = 'filament.resources.crop-resource.pages.list-crops';

    // Set default sort for the page
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'id';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    public function mount(): void
    {
        parent::mount();
        
        // Clear any cached sort state that might reference the old crops.planting_at column
        if ($this->getTableSortColumn() === 'planting_at' || str_contains($this->getTableSortColumn() ?? '', 'crops.')) {
            $this->resetTableSorting();
        }
        
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
        
        // Remove any old ordering that might reference crops table columns
        $query->reorder();
        
        return $query;
    }
    
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();
        
        // Log the queries for debugging
        $queries = DB::getQueryLog();
        if (!empty($queries)) {
            Log::info('Grows List Query Count (Using View):', [
                'total_queries' => count($queries),
                'queries' => array_map(function($q) { 
                    return substr($q['query'], 0, 100) . '...'; 
                }, array_slice($queries, -10)), // Last 10 queries
            ]);
        }
        
        return $records;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            $this->getActiveSoakingButton(),
            ...parent::getHeaderActions(),
        ];
    }
    
    public function getTableRefreshInterval(): ?string
    {
        // Refresh the table every 5 minutes to update time values
        return '5m';
    }
    
    /**
     * Get the count of crops currently soaking
     */
    protected function getActiveSoakingCount(): int
    {
        $soakingStage = CropStageCache::findByCode('soaking');
        
        if (!$soakingStage) {
            return 0;
        }

        return Crop::where('current_stage_id', $soakingStage->id)
            ->where('requires_soaking', true)
            ->whereNotNull('soaking_at')
            ->count();
    }
    
    /**
     * Check if any soaking crops are overdue
     */
    protected function hasOverdueSoaking(): bool
    {
        $soakingStage = CropStageCache::findByCode('soaking');
        
        if (!$soakingStage) {
            return false;
        }

        $soakingCrops = Crop::with('recipe')
            ->where('current_stage_id', $soakingStage->id)
            ->where('requires_soaking', true)
            ->whereNotNull('soaking_at')
            ->get();
            
        foreach ($soakingCrops as $crop) {
            $timeRemaining = $crop->getSoakingTimeRemaining();
            if ($timeRemaining !== null && $timeRemaining <= 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the Active Soaking button for the header
     */
    protected function getActiveSoakingButton(): Actions\Action
    {
        $count = $this->getActiveSoakingCount();
        $hasOverdue = $this->hasOverdueSoaking();
        
        return Actions\Action::make('activeSoaking')
            ->label($count > 0 ? "Active Soaking ({$count})" : "Active Soaking")
            ->icon('heroicon-o-beaker')
            ->color($count > 0 ? ($hasOverdue ? 'danger' : 'success') : 'gray')
            ->url('/admin/active-soaking')
            ->disabled($count === 0)
            ->tooltip($count === 0 ? 'No crops are currently soaking' : null);
    }
} 