<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsumableResource\Forms\ConsumableForm;
use App\Filament\Resources\ConsumableResource\Pages;
use App\Filament\Resources\ConsumableResource\Tables\ConsumableTable;
use App\Filament\Resources\ConsumableResource\Tables\ConsumableTableActions;
use App\Models\Consumable;
use App\Models\ConsumableUnit;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Traits\CsvExportAction;

/**
 * Consumable Resource - Refactored following Filament Resource Architecture Guide
 * Reduced from 554 lines to delegation pattern
 * Form logic → ConsumableForm (552 lines)
 * Table logic → ConsumableTable (318 lines)
 * Table actions → ConsumableTableActions (95 lines)
 */
class ConsumableResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'All Consumables';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 10; // Lower priority to appear after specialized resources

    public static function form(Form $form): Form
    {
        return $form->schema(ConsumableForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => ConsumableTable::modifyQuery($query))
            ->columns(ConsumableTable::columns())
            ->defaultSort(ConsumableTable::getDefaultSort())
            ->filters(ConsumableTable::filters())
            ->groups(ConsumableTable::groups())
            ->actions(ConsumableTableActions::actions())
            ->bulkActions(ConsumableTableActions::bulkActions())
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsumables::route('/'),
            'create' => Pages\CreateConsumable::route('/create'),
            'view' => Pages\ViewConsumable::route('/{record}'),
            'edit' => Pages\EditConsumable::route('/{record}/edit'),
            'adjust-stock' => Pages\AdjustStock::route('/{record}/adjust-stock'),
        ];
    }
    
    /**
     * Define CSV export columns for Consumables
     */
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'supplier' => ['name', 'email'],
            'masterSeedCatalog' => ['common_name', 'category'],
            'packagingType' => ['name', 'capacity_volume', 'volume_unit'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier', 'masterSeedCatalog', 'packagingType'];
    }

    /**
     * Get compatible units for a consumable for unit conversion
     * 
     * @param Consumable $record The consumable record
     * @return array Compatible units
     */
    public static function getCompatibleUnits(Consumable $record): array
    {
        if (!$record->consumableUnit) {
            return ['unit' => 'Unit(s)'];
        }
        
        // Get compatible units from the same category
        $compatibleUnits = ConsumableUnit::byCategory($record->consumableUnit->category)
            ->pluck('display_name', 'code')
            ->toArray();
        
        return $compatibleUnits;
    }

    /**
     * Get human-readable label for unit
     * 
     * @param string $unit Unit code
     * @return string Human-readable unit label
     */
    public static function getUnitLabel(string $unit): string
    {
        $labels = [
            'unit' => 'Unit(s)',
            'kg' => 'Kilograms',
            'g' => 'Grams',
            'oz' => 'Ounces',
            'l' => 'Litre(s)',
            'ml' => 'Milliliters',
        ];
        
        return $labels[$unit] ?? $unit;
    }
}