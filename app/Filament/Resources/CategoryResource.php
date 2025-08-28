<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\CategoryResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\Category;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends BaseResource
{
    protected static ?string $model = Category::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Product Categories';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getBasicInformationSection([
                    static::getNameField(),
                    static::getDescriptionField(),
                    static::getActiveToggleField(),
                ])
                ->heading('Category Information'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureStandardTable(
            $table,
            columns: [
                static::getTextColumn('name', 'Name'),
                static::getTruncatedTextColumn('description', 'Description'),
                static::getActiveBadgeColumn(),
                static::getCountColumn('products', 'Products'),
            ],
            filters: [
                static::getActiveStatusFilter()
                    ->placeholder('All Categories')
                    ->trueLabel('Active Categories')
                    ->falseLabel('Inactive Categories'),
            ]
        );
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
