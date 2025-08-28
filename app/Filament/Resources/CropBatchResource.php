<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\CropBatchResource\Forms\CropBatchEditForm;
use App\Filament\Resources\CropBatchResource\Pages\ListCropBatches;
use App\Filament\Resources\CropBatchResource\Pages\CreateCropBatch;
use App\Filament\Resources\CropBatchResource\Pages\ViewCropBatch;
use App\Filament\Resources\CropBatchResource\Pages\EditCropBatch;
use App\Filament\Resources\CropBatchResource\Pages;
use App\Filament\Resources\CropResource\Forms\CropBatchForm;
use App\Filament\Resources\CropResource\Infolists\CropBatchInfolist;
use App\Filament\Resources\CropResource\Tables\CropBatchTable;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasTimestamps;
use App\Models\CropBatch;
use App\Models\CropBatchListView;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CropBatchResource extends BaseResource
{
    use CsvExportAction;
    use HasStandardActions;
    use HasTimestamps;

    protected static ?string $model = CropBatch::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Crop Batches';

    protected static string | \UnitEnum | null $navigationGroup = 'Production';

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralModelLabel = 'Crop Batches';

    protected static ?string $modelLabel = 'Crop Batch';
    
    protected static ?string $slug = 'crop-batches';

    protected static ?string $recordTitleAttribute = 'recipe_name';

    public static function form(Schema $schema): Schema
    {
        // Use different forms for create vs edit
        if ($schema->getOperation() === 'edit') {
            return $schema->components(CropBatchEditForm::schema());
        }
        
        return $schema->components(CropBatchForm::schema());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components(CropBatchInfolist::schema());
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
            ->recordActions(CropBatchTable::actions())
            ->toolbarActions(CropBatchTable::bulkActions())
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['recipe', 'crops.recipe', 'crops.currentStage']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCropBatches::route('/'),
            'create' => CreateCropBatch::route('/create'),
            'view' => ViewCropBatch::route('/{record}'),
            'edit' => EditCropBatch::route('/{record}/edit'),
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
