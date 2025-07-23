<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ProductInventoryResource\Forms\ProductInventoryForm;
use App\Filament\Resources\ProductInventoryResource\Pages;
use App\Filament\Resources\ProductInventoryResource\RelationManagers;
use App\Filament\Resources\ProductInventoryResource\Tables\ProductInventoryTable;
use App\Models\ProductInventory;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductInventoryResource extends BaseResource
{
    protected static ?string $model = ProductInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Product Inventory';

    protected static ?string $pluralLabel = 'Product Inventory';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationGroup = 'Products';

    public static function form(Form $form): Form
    {
        return $form->schema(ProductInventoryForm::schema());
    }

    public static function table(Table $table): Table
    {
        $emptyStateConfig = ProductInventoryTable::getEmptyStateConfig();
        
        return $table
            ->columns(ProductInventoryTable::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(ProductInventoryTable::filters())
            ->actions(ProductInventoryTable::actions())
            ->bulkActions(ProductInventoryTable::bulkActions())
            ->emptyStateHeading($emptyStateConfig['heading'])
            ->emptyStateDescription($emptyStateConfig['description'])
            ->emptyStateIcon($emptyStateConfig['icon']);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductInventories::route('/'),
            'create' => Pages\CreateProductInventory::route('/create'),
            'view' => Pages\ViewProductInventory::route('/{record}'),
            'edit' => Pages\EditProductInventory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'product',
                'priceVariation.packagingType.unitType'
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['lot_number', 'product.name'];
    }
}