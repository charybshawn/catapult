<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ProductPhotoResource\Pages\ListProductPhotos;
use App\Filament\Resources\ProductPhotoResource\Pages\CreateProductPhoto;
use App\Filament\Resources\ProductPhotoResource\Pages\EditProductPhoto;
use App\Filament\Resources\ProductPhotoResource\Pages;
use App\Filament\Resources\ProductPhotoResource\RelationManagers;
use App\Models\ItemPhoto;
use Filament\Forms;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductPhotoResource extends BaseResource
{
    protected static ?string $model = ItemPhoto::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Product Photos';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_id')
                    ->relationship('item', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                FileUpload::make('photo')
                    ->required()
                    ->image()
                    ->directory('item-photos')
                    ->maxSize(5120)
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('1200'),
                TextInput::make('caption')
                    ->maxLength(255),
                TextInput::make('order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_default')
                    ->label('Default Photo')
                    ->helperText('This will be shown as the main product image')
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set, $get, $record) {
                        if (!$state || !$record) {
                            return;
                        }
                        
                        // If this is being set as default, make sure no other photos for this item are default
                        ItemPhoto::where('item_id', $record->item_id)
                            ->where('id', '!=', $record->id)
                            ->where('is_default', true)
                            ->update(['is_default' => false]);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                TextColumn::make('item.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('photo')
                    ->width(100)
                    ->height(100),
                TextColumn::make('caption')
                    ->searchable(),
                TextColumn::make('order')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_default')
                    ->label('Default Photo'),
            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip('Edit photo'),
                DeleteAction::make()
                    ->tooltip('Delete photo'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListProductPhotos::route('/'),
            'create' => CreateProductPhoto::route('/create'),
            'edit' => EditProductPhoto::route('/{record}/edit'),
        ];
    }
} 