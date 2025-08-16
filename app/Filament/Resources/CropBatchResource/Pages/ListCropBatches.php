<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use App\Http\Resources\CropBatchListResource;
use App\Models\CropBatch;
use App\Services\CropBatchDisplayService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListCropBatches extends ListRecords
{
    protected static string $resource = CropBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createCrop')
                ->label('New Crop')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url('/admin/crops/create')
                ->button(),
            Actions\Action::make('viewIndividual')
                ->label('View Individual Crops')
                ->icon('heroicon-o-list-bullet')
                ->color('info')
                ->url('/admin/crops')
                ->tooltip('Switch to individual crop view'),
        ];
    }

    /**
     * Override the table query to use optimized CropBatch scopes
     */
    protected function getTableQuery(): ?Builder
    {
        return CropBatch::forListDisplay()->activeOnly();
    }

    /**
     * Transform records using the service layer for calculated fields
     */
    public function getTableRecords(): Collection
    {
        $cropBatches = $this->getTableQuery()->get();
        
        $transformedData = app(CropBatchDisplayService::class)
            ->transformForTable($cropBatches);
        
        // Convert to a collection that Filament can work with
        return collect($transformedData->map(function ($data) {
            return (object) $data;
        }));
    }
}