<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterSeedCatalogResource\Pages;
use App\Filament\Resources\MasterSeedCatalogResource\RelationManagers;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
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
                Forms\Components\Section::make('Seed Information')
                    ->schema([
                        Forms\Components\TextInput::make('common_name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->helperText('e.g. Radish, Cress, Peas, Sunflower'),
                        Forms\Components\TagsInput::make('cultivars')
                            ->label('Cultivars')
                            ->helperText('Enter cultivar names for this seed type (e.g. Cherry Belle, French Breakfast)'),


                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns([
                Tables\Columns\TextColumn::make('common_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cultivars')
                    ->label('Cultivars')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([

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
