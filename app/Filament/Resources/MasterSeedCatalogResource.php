<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\MasterSeedCatalogResource\Pages\ListMasterSeedCatalogs;
use App\Filament\Resources\MasterSeedCatalogResource\Pages\CreateMasterSeedCatalog;
use App\Filament\Resources\MasterSeedCatalogResource\Pages\EditMasterSeedCatalog;
use App\Filament\Resources\MasterSeedCatalogResource\Pages;
use App\Filament\Resources\MasterSeedCatalogResource\RelationManagers;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
use Filament\Forms;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Traits\CsvExportAction;

class MasterSeedCatalogResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = MasterSeedCatalog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    protected static ?string $navigationLabel = 'Master Seed Catalog';
    protected static ?string $pluralLabel = 'Master Seed Catalog';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('common_name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('e.g. Radish, Cress, Peas, Sunflower'),
                Select::make('cultivar_id')
                    ->label('Primary Cultivar')
                    ->relationship('cultivar', 'cultivar_name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('cultivar_name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description'),
                        TagsInput::make('aliases')
                            ->helperText('Alternative names for this cultivar'),
                    ])
                    ->helperText('Select or create the primary cultivar for this seed type'),
                Select::make('category')
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
                TagsInput::make('aliases')
                    ->helperText('Alternative names for this seed type'),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns([
                TextColumn::make('common_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cultivar.cultivar_name')
                    ->label('Primary Cultivar')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('category')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category'),
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
            ->headerActions([
                static::getCsvExportAction(),
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
            'index' => ListMasterSeedCatalogs::route('/'),
            'create' => CreateMasterSeedCatalog::route('/create'),
            'edit' => EditMasterSeedCatalog::route('/{record}/edit'),
        ];
    }
    
    /**
     * Define CSV export columns for Master Seed Catalog
     */
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'cultivar' => ['cultivar_name', 'is_active'],
            'products' => ['name', 'base_price'],
            'recipes' => ['name'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['cultivar', 'products', 'recipes'];
    }
}
