<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ProductResource\Forms\ProductForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Tables\ProductTable;
use App\Filament\Traits\CsvExportAction;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Products';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema(ProductForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => ProductTable::modifyQuery($query))
            ->columns([
                static::getNameColumn(),
                ...array_slice(ProductTable::columns(), 1), // Skip the first column (name) and use the rest
                ...static::getTimestampColumns(),
            ])
            ->filters(ProductTable::filters())
            ->actions(ProductTable::actions())
            ->bulkActions(ProductTable::bulkActions())
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    /**
     * Define CSV export columns for Products - uses automatic detection from schema
     * Optionally add relationship columns manually
     */
    protected static function getCsvExportColumns(): array
    {
        // Get automatically detected columns from database schema
        $autoColumns = static::getColumnsFromSchema();
        
        // Add relationship columns
        return static::addRelationshipColumns($autoColumns, [
            'category' => ['name'],
            'masterSeedCatalog' => ['common_name', 'cultivars'],
            'productMix' => ['name'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['category', 'masterSeedCatalog', 'productMix'];
    }

    /**
     * Get the panels that should be displayed for viewing a record.
     */
    public static function getPanels(): array
    {
        return ProductForm::getPanels();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
    /**
     * Get the single-page form schema
     */
    public static function getSinglePageFormSchema(): array
    {
        return ProductForm::getSinglePageFormSchema();
    }

    /**
     * Get the price variation management field with modal template selector
     */
    public static function getPriceVariationSelectionField(): Forms\Components\Component
    {
        return ProductForm::getPriceVariationSelectionField();
    }

    
} 