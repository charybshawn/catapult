<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropBatchResource\Pages;
use App\Filament\Resources\CropResource\Forms\CropBatchForm;
use App\Filament\Resources\CropResource\Tables\CropBatchTable;
use App\Filament\Resources\CropResource\Infolists\CropBatchInfolist;
use App\Models\CropBatchListView;
use App\Models\RecipeOptimizedView;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStandardActions;

class CropBatchResource extends BaseResource
{
    use CsvExportAction;
    use HasTimestamps;
    use HasStandardActions;
    
    protected static ?string $model = CropBatchListView::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Crop Batches';
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 3;
    
    protected static ?string $recordTitleAttribute = 'recipe_name';

    public static function form(Form $form): Form
    {
        return $form->schema(CropBatchForm::schema());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(CropBatchInfolist::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->defaultSort('id', 'desc')
            ->deferLoading()
            ->recordAction('view')
            ->recordUrl(null)
            ->columns(CropBatchTable::columns())
            ->groups(CropBatchTable::groups())
            ->filters(CropBatchTable::filters())
            ->actions(CropBatchTable::actions())
            ->bulkActions(CropBatchTable::bulkActions())
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropBatches::route('/'),
            'create' => Pages\CreateCropBatch::route('/create'),
            'view' => Pages\ViewCropBatch::route('/{record}'),
            'edit' => Pages\EditCropBatch::route('/{record}/edit'),
        ];
    }
    
    /**
     * Define CSV export columns for Crop Batches
     */
    protected static function getCsvExportColumns(): array
    {
        return [
            'id' => 'Batch ID',
            'recipe_name' => 'Recipe Name',
            'crop_count' => 'Tray Count',
            'current_stage_name' => 'Current Stage',
            'planting_at' => 'Planted Date',
            'expected_harvest_at' => 'Expected Harvest',
            'stage_age_display' => 'Time in Stage',
            'total_age_display' => 'Total Age',
            'created_at' => 'Created At',
        ];
    }
}