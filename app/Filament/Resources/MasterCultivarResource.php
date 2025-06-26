<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterCultivarResource\Pages;
use App\Filament\Resources\MasterCultivarResource\RelationManagers;
use App\Models\MasterCultivar;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MasterCultivarResource extends Resource
{
    protected static ?string $model = MasterCultivar::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?string $navigationLabel = 'Cultivars';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('master_seed_catalog_id')
                    ->label('Seed Type')
                    ->relationship('masterSeedCatalog', 'common_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('cultivar_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TagsInput::make('aliases')
                    ->helperText('Alternative names for this cultivar'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('growing_notes')
                    ->columnSpanFull()
                    ->helperText('Specific growing notes for this cultivar'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('masterSeedCatalog.common_name')
                    ->label('Seed Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cultivar_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('masterSeedCatalog.category')
                    ->label('Category')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('master_seed_catalog_id')
                    ->label('Seed Type')
                    ->relationship('masterSeedCatalog', 'common_name'),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListMasterCultivars::route('/'),
            'create' => Pages\CreateMasterCultivar::route('/create'),
            'edit' => Pages\EditMasterCultivar::route('/{record}/edit'),
        ];
    }
}
