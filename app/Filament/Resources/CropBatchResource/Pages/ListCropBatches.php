<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use App\Models\CropBatchListView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

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
     * Override the table query to use CropBatchListView for performance
     */
    protected function getTableQuery(): ?Builder
    {
        return CropBatchListView::query();
    }
}