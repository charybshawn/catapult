<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Filament\Resources\Base\BaseResource;
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
    
    protected static ?string $navigationGroup = 'Sales & Products';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Product Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormCommon::basicInformationSection()
                    ->heading('Category Information'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getTextColumn('name', 'Name'),
                static::getTruncatedTextColumn('description', 'Description'),
                static::getActiveBadgeColumn(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Products')
                    ->counts('items')
                    ->sortable()
                    ->color('primary'),
                ...static::getTimestampColumns(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All Categories')
                    ->trueLabel('Active Categories')
                    ->falseLabel('Inactive Categories'),
            ])
            ->actions(static::getDefaultTableActions())
            ->bulkActions([static::getDefaultBulkActions()]);
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
