<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\MasterCultivarResource\Pages\ListMasterCultivars;
use App\Filament\Resources\MasterCultivarResource\Pages\CreateMasterCultivar;
use App\Filament\Resources\MasterCultivarResource\Pages\EditMasterCultivar;
use App\Filament\Resources\MasterCultivarResource\Pages;
use App\Filament\Resources\MasterCultivarResource\RelationManagers;
use App\Models\MasterCultivar;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MasterCultivarResource extends BaseResource
{
    protected static ?string $model = MasterCultivar::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    protected static ?string $navigationLabel = 'Cultivars';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('master_seed_catalog_id')
                    ->label('Seed Type')
                    ->relationship('masterSeedCatalog', 'common_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('cultivar_name')
                    ->required()
                    ->maxLength(255),
                TagsInput::make('aliases')
                    ->helperText('Alternative names for this cultivar'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns([
                TextColumn::make('masterSeedCatalog.common_name')
                    ->label('Seed Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cultivar_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('masterSeedCatalog.category')
                    ->label('Category')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('master_seed_catalog_id')
                    ->label('Seed Type')
                    ->relationship('masterSeedCatalog', 'common_name'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->tooltip('View record'),
                    EditAction::make()->tooltip('Edit record'),
                    DeleteAction::make()->tooltip('Delete record'),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
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
            'index' => ListMasterCultivars::route('/'),
            'create' => CreateMasterCultivar::route('/create'),
            'edit' => EditMasterCultivar::route('/{record}/edit'),
        ];
    }
}
