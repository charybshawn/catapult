<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedEntryResource\Pages;
use App\Filament\Resources\SeedEntryResource\RelationManagers;
use App\Models\SeedEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class SeedEntryResource extends Resource
{
    protected static ?string $model = SeedEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    
    protected static ?string $navigationLabel = 'Seed Entries';
    
    protected static ?string $navigationGroup = 'Seed Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('seed_cultivar_id')
                    ->relationship('seedCultivar', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535),
                    ]),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('supplier_product_title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('supplier_product_url')
                    ->required()
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('image_url')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TagsInput::make('tags')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seedCultivar.name')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier_product_title')
                    ->label('Product Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('variations_count')
                    ->counts('variations')
                    ->label('Variations')
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
                Tables\Filters\SelectFilter::make('cultivar')
                    ->relationship('seedCultivar', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Cultivar'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('visit_url')
                    ->label('Visit URL')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (SeedEntry $record) => $record->supplier_product_url)
                    ->openUrlInNewTab(),
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
            RelationManagers\VariationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedEntries::route('/'),
            'create' => Pages\CreateSeedEntry::route('/create'),
            'view' => Pages\ViewSeedEntry::route('/{record}'),
            'edit' => Pages\EditSeedEntry::route('/{record}/edit'),
        ];
    }
} 