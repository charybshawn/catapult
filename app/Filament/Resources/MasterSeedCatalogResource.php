<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterSeedCatalogResource\Pages;
use App\Filament\Traits\CsvExportAction;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Section::make('')
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
                    ->formatStateUsing(function ($state) {
                        if (is_string($state) && ! empty($state)) {
                            // Try JSON decode first
                            $decoded = json_decode($state, true);

                            // If JSON decode fails, treat as comma-separated string
                            if ($decoded === null) {
                                $decoded = array_map('trim', explode(',', $state));
                            }

                            if (is_array($decoded) && ! empty($decoded)) {
                                $badges = '';
                                foreach ($decoded as $item) {
                                    $capitalized = ucfirst(strtolower(trim($item)));
                                    $badges .= '<span class="inline-flex items-center px-2 py-1 mr-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-200">'.$capitalized.'</span>';
                                }

                                return $badges;
                            }
                        }

                        return 'No cultivars';
                    })
                    ->html()
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

    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();

        return static::addRelationshipColumns($autoColumns, [
            'products' => ['name', 'base_price'],
            'recipes' => ['name'],
        ]);
    }

    protected static function getCsvExportRelationships(): array
    {
        return ['products', 'recipes'];
    }
}
