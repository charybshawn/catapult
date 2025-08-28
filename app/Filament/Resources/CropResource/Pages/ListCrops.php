<?php

namespace App\Filament\Resources\CropResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Recipe;
use Filament\Actions\CreateAction;
use App\Models\CropStage;
use Filament\Actions\Action;
use App\Filament\Resources\CropResource;
use App\Models\CropBatch;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListCrops extends ListRecords
{
    protected static string $resource = CropResource::class;

    protected string $view = 'filament.resources.crop-resource.pages.list-crops';

    // Set default sort for the page - use batch_date for grouped view
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

        // Clear previous query log
        DB::flushQueryLog();

        // Enable query logging for debugging
        DB::enableQueryLog();
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Add additional logging for debugging
        Log::info('Recipe and Seed Entry Data:', [
            'recipes' => Recipe::with('masterSeedCatalog', 'masterCultivar')->get()->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'common_name' => $recipe->common_name,
                    'cultivar_name' => $recipe->cultivar_name,
                ];
            }),
        ]);

        // No need for complex ordering - CropBatch uses simple columns
        return $query;
    }

    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        // Log the queries for debugging
        $queries = DB::getQueryLog();
        if (! empty($queries)) {
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
            CreateAction::make(),
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
        $soakingStage = CropStage::findByCode('soaking');

        if (! $soakingStage) {
            return 0;
        }

        // Count batches that are in soaking stage
        return CropBatch::whereHas('crops', function ($query) use ($soakingStage) {
            $query->where('current_stage_id', $soakingStage->id)
                ->where('requires_soaking', true)
                ->whereNotNull('soaking_at');
        })->count();
    }

    /**
     * Check if any soaking crops are overdue
     */
    protected function hasOverdueSoaking(): bool
    {
        $soakingStage = CropStage::findByCode('soaking');

        if (! $soakingStage) {
            return false;
        }

        $soakingBatches = CropBatch::with('crops.recipe')
            ->whereHas('crops', function ($query) use ($soakingStage) {
                $query->where('current_stage_id', $soakingStage->id)
                    ->where('requires_soaking', true)
                    ->whereNotNull('soaking_at');
            })->get();

        foreach ($soakingBatches as $batch) {
            foreach ($batch->crops as $crop) {
                $timeRemaining = $crop->getSoakingTimeRemaining();
                if ($timeRemaining !== null && $timeRemaining <= 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the Active Soaking button for the header
     */
    protected function getActiveSoakingButton(): Action
    {
        $count = $this->getActiveSoakingCount();
        $hasOverdue = $this->hasOverdueSoaking();

        return Action::make('activeSoaking')
            ->label($count > 0 ? "Active Soaking ({$count})" : 'Active Soaking')
            ->icon('heroicon-o-beaker')
            ->color($count > 0 ? ($hasOverdue ? 'danger' : 'success') : 'gray')
            ->url('/admin/active-soaking')
            ->disabled($count === 0)
            ->tooltip($count === 0 ? 'No crops are currently soaking' : null);
    }
}
