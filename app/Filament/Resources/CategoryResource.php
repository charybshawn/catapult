<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends BaseResource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Product Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                static::getBasicInformationSection([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
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
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
