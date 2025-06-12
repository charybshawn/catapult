<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterSeedCatalogResource\Pages;
use App\Filament\Resources\MasterSeedCatalogResource\RelationManagers;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MasterSeedCatalogResource extends Resource
{
    protected static ?string $model = MasterSeedCatalog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?string $navigationLabel = 'Master Seed Catalog';
    protected static ?string $pluralLabel = 'Master Seed Catalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('common_name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('e.g. Radish, Cress, Peas, Sunflower'),
                Forms\Components\TagsInput::make('cultivars')
                    ->label('Cultivars')
                    ->helperText('Enter multiple cultivar names, e.g. Cherry Belle, French Breakfast, Watermelon'),
                Forms\Components\Select::make('category')
                    ->options([
                        'Herbs' => 'Herbs',
                        'Brassicas' => 'Brassicas',
                        'Legumes' => 'Legumes',
                        'Greens' => 'Greens',
                        'Grains' => 'Grains',
                        'Shoots' => 'Shoots',
                        'Other' => 'Other',
                    ])
                    ->searchable(),
                Forms\Components\TagsInput::make('aliases')
                    ->helperText('Alternative names for this seed type'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('common_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ViewColumn::make('cultivars')
                    ->label('Cultivars')
                    ->toggleable()
                    ->view('filament.columns.cultivars-badges'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cultivars_count')
                    ->counts('cultivars')
                    ->label('Cultivars'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMasterSeedCatalogs::route('/'),
            'create' => Pages\CreateMasterSeedCatalog::route('/create'),
            'edit' => Pages\EditMasterSeedCatalog::route('/{record}/edit'),
        ];
    }
}
