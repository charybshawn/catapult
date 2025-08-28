<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\ProductInventoryResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\ProductInventoryResource\RelationManagers\ReservationsRelationManager;
use App\Filament\Resources\ProductInventoryResource\Pages\ListProductInventories;
use App\Filament\Resources\ProductInventoryResource\Pages\CreateProductInventory;
use App\Filament\Resources\ProductInventoryResource\Pages\ViewProductInventory;
use App\Filament\Resources\ProductInventoryResource\Pages\EditProductInventory;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ProductInventoryResource\Forms\ProductInventoryForm;
use App\Filament\Resources\ProductInventoryResource\Pages;
use App\Filament\Resources\ProductInventoryResource\RelationManagers;
use App\Filament\Resources\ProductInventoryResource\Tables\ProductInventoryTable;
use App\Models\ProductInventory;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductInventoryResource extends BaseResource
{
    protected static ?string $model = ProductInventory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Product Inventory';

    protected static ?string $pluralLabel = 'Product Inventory';

    protected static ?int $navigationSort = 30;

    protected static string | \UnitEnum | null $navigationGroup = 'Products';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(ProductInventoryForm::schema());
    }

    public static function table(Table $table): Table
    {
        $emptyStateConfig = ProductInventoryTable::getEmptyStateConfig();
        
        return $table
            ->columns(ProductInventoryTable::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(ProductInventoryTable::filters())
            ->recordActions(ProductInventoryTable::actions())
            ->toolbarActions(ProductInventoryTable::bulkActions())
            ->emptyStateHeading($emptyStateConfig['heading'])
            ->emptyStateDescription($emptyStateConfig['description'])
            ->emptyStateIcon($emptyStateConfig['icon']);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
            ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductInventories::route('/'),
            'create' => CreateProductInventory::route('/create'),
            'view' => ViewProductInventory::route('/{record}'),
            'edit' => EditProductInventory::route('/{record}/edit'),
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