<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsumableResource\Pages;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use Illuminate\Support\Facades\Log;
use App\Filament\Tables\Components\Common as TableCommon;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasInventoryStatus;
use App\Filament\Resources\Consumables\Components\ConsumableFormComponents;
use App\Filament\Resources\Consumables\Components\ConsumableTableComponents;

abstract class ConsumableResourceBase extends BaseResource
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;
    use ConsumableFormComponents;
    use ConsumableTableComponents;
    
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 1;

    /**
     * Get the consumable type code for this resource
     */
    abstract public static function getConsumableTypeCode(): string;
    
    /**
     * Get type-specific form schema
     */
    abstract protected static function getTypeSpecificFormSchema(bool $isEditMode): array;
    
    /**
     * Get type-specific table columns
     */
    abstract protected static function getTypeSpecificTableColumns(): array;
    
    /**
     * Get inventory details schema - to be implemented by subclasses
     */
    abstract protected static function getInventoryDetailsSchema(bool $isEditMode): array;

    public static function form(Form $form): Form
    {
        // Determine if we're in edit mode
        $isEditMode = $form->getOperation() === 'edit';
        
        return $form
            ->schema(static::getFormSchema($isEditMode));
    }
    
    /**
     * Get the complete form schema
     */
    protected static function getFormSchema(bool $isEditMode): array
    {
        return [
            Forms\Components\Section::make('Basic Information')
                ->schema(array_merge(
                    [
                        // Hidden field for consumable type (set by sub-resource)
                        Forms\Components\Hidden::make('consumable_type_id')
                            ->default(fn() => ConsumableType::findByCode(static::getConsumableTypeCode())?->id)
                            ->dehydrated(),
                    ],
                    static::getTypeSpecificFormSchema($isEditMode),
                    [
                        static::getActiveStatusField()
                            ->columnSpanFull(),
                    ]
                ))
                ->columns(2),
            
            Forms\Components\Section::make('Inventory Details')
                ->schema(static::getInventoryDetailsSchema($isEditMode))
                ->columns(3),
            
            static::getCostInformationSection()
                ->collapsed(),
                
            static::getAdditionalInformationSection()
                ->collapsed(),
        ];
    }

    public static function table(Table $table): Table
    {
        return static::configureCommonTable($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'supplier',
                'consumableType',
                'consumableUnit',
                'masterSeedCatalog',
                'packagingType'
            ])->whereHas('consumableType', fn ($q) => $q->where('code', static::getConsumableTypeCode())))
            ->columns(array_merge(
                static::getCommonTableColumns(),
                static::getTypeSpecificTableColumns()
            ))
            ->defaultSort(function (Builder $query) {
                return $query->orderByRaw('(initial_stock - consumed_quantity) ASC');
            })
            ->filters(array_merge(
                static::getCommonFilters(),
                static::getTypeSpecificFilters()
            ))
            ->groups(static::getCommonGroups())
            ->actions(static::getStandardTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ...static::getStandardBulkActions(),
                    ...static::getInventoryBulkActions(),
                ]),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }
    
    /**
     * Get type-specific filters for table
     */
    protected static function getTypeSpecificFilters(): array
    {
        return [];
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