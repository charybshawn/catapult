<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use App\Filament\Resources\ProductMixResource\Pages\ListProductMixes;
use App\Filament\Resources\ProductMixResource\Pages\CreateProductMix;
use App\Filament\Resources\ProductMixResource\Pages\EditProductMix;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ProductMixResource\Forms\ProductMixForm;
use App\Filament\Resources\ProductMixResource\Pages;
use App\Filament\Resources\ProductMixResource\Tables\ProductMixTable;
use App\Models\ProductMix;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ProductMixResource extends BaseResource
{
    protected static ?string $model = ProductMix::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'Product Mixes';
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(ProductMixForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->modifyQueryUsing(fn (Builder $query) => ProductMixTable::modifyQuery($query))
            ->columns([
                static::getClickableNameColumn('Name'),
                ...array_slice(ProductMixTable::columns(), 1), // Skip the first column (name) and use the rest
            ])
            ->defaultSort('name', 'asc')
            ->filters(ProductMixTable::filters())
            ->recordActions(ProductMixTable::actions())
            ->toolbarActions(ProductMixTable::bulkActions())
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductMixes::route('/'),
            'create' => CreateProductMix::route('/create'),
            'edit' => EditProductMix::route('/{record}'),
        ];
    }
} 