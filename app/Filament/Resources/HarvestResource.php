<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HarvestResource\Forms\HarvestForm;
use App\Filament\Resources\HarvestResource\Pages;
use App\Filament\Resources\HarvestResource\Tables\HarvestTable;
use App\Models\Harvest;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Filament\Traits\CsvExportAction;

class HarvestResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = Harvest::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Harvests';

    protected static ?string $navigationGroup = 'Production';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationParentItem = 'Grows';

    public static function form(Form $form): Form
    {
        return $form->schema(HarvestForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn ($query) => HarvestTable::modifyQuery($query))
            ->columns(HarvestTable::columns())
            ->defaultSort('harvest_date', 'desc')
            ->groups(HarvestTable::groups())
            ->defaultGroup('harvest_date')
            ->groupsInDropdownOnDesktop()
            ->filters(HarvestTable::filters())
            ->actions(HarvestTable::actions())
            ->headerActions([
                static::getCsvExportAction(),
            ])
            ->bulkActions(HarvestTable::bulkActions());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHarvests::route('/'),
            'edit' => Pages\EditHarvest::route('/{record}/edit'),
        ];
    }
    
    /**
     * Define CSV export columns for Harvests
     */
    protected static function getCsvExportColumns(): array
    {
        $coreColumns = [
            'id' => 'ID',
            'master_cultivar_id' => 'Cultivar ID',
            'total_weight_grams' => 'Total Weight (g)',
            'tray_count' => 'Tray Count',
            'harvest_date' => 'Harvest Date',
            'user_id' => 'User ID',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
        
        return static::addRelationshipColumns($coreColumns, [
            'masterCultivar' => ['common_name', 'cultivar_name'],
            'user' => ['name', 'email'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['masterCultivar', 'user'];
    }
}
