<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterSeedCatalogResource\Pages;
use App\Filament\Resources\MasterSeedCatalogResource\RelationManagers;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use Filament\Forms\Form;
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
        return static::configureTableDefaults($table)
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->tooltip('View record'),
                    Tables\Actions\EditAction::make()->tooltip('Edit record'),
                    Tables\Actions\DeleteAction::make()->tooltip('Delete record'),
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
    
    /**
     * Define CSV export columns for Master Seed Catalog
     */
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'masterCultivars' => ['cultivar_name', 'is_active'],
            'products' => ['name', 'base_price'],
            'recipes' => ['name'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['masterCultivars', 'products', 'recipes'];
    }
}
