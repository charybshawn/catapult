<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductPhotoResource\Pages;
use App\Filament\Resources\ProductPhotoResource\RelationManagers;
use App\Models\ItemPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductPhotoResource extends BaseResource
{
    protected static ?string $model = ItemPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Product Photos';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->relationship('item', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\FileUpload::make('photo')
                    ->required()
                    ->image()
                    ->directory('item-photos')
                    ->maxSize(5120)
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('1200'),
                Forms\Components\TextInput::make('caption')
                    ->maxLength(255),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_default')
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
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('photo')
                    ->width(100)
                    ->height(100),
                Tables\Columns\TextColumn::make('caption')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Photo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit photo'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete photo'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProductPhotos::route('/'),
            'create' => Pages\CreateProductPhoto::route('/create'),
            'edit' => Pages\EditProductPhoto::route('/{record}/edit'),
        ];
    }
} 