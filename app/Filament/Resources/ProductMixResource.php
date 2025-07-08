<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductMixResource\Pages;
use App\Models\ProductMix;
use App\Forms\Components\CompactRepeater;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\RecipeVarietyService;

class ProductMixResource extends Resource
{
    protected static ?string $model = ProductMix::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'Product Mixes';
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Mix Name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Mix Components')
                    ->schema([
                        // Total percentage display
                        Forms\Components\Placeholder::make('percentage_total')
                            ->label('')
                            ->content(function ($get) {
                                $components = $get('masterSeedCatalogs') ?? [];
                                $total = 0;
                                
                                foreach ($components as $component) {
                                    if (isset($component['percentage']) && is_numeric($component['percentage'])) {
                                        $total += floatval($component['percentage']);
                                    }
                                }
                                
                                // Round to 2 decimal places to match database precision
                                $total = round($total, 2);
                                // Format to show only needed decimal places
                                $totalFormatted = number_format($total, 2);
                                
                                if ($total == 0) {
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Add varieties to see total percentage</p>
                                        </div>
                                    ');
                                } elseif ($total == 100) {
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border-2 border-green-500">
                                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">✓ ' . $totalFormatted . '%</p>
                                            <p class="text-sm text-green-600 dark:text-green-400">Perfect mix!</p>
                                        </div>
                                    ');
                                } else {
                                    $difference = 100 - $total;
                                    $differenceText = $difference > 0 
                                        ? 'Add ' . number_format($difference, 2) . '% more'
                                        : 'Remove ' . number_format(abs($difference), 2) . '%';
                                    
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-500">
                                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">⚠️ ' . $totalFormatted . '%</p>
                                            <p class="text-sm text-amber-600 dark:text-amber-400">' . $differenceText . ' to reach 100%</p>
                                        </div>
                                    ');
                                }
                            })
                            ->extraAttributes(['class' => 'w-full'])
                            ->reactive(),
                            
                        CompactRepeater::make('mixComponents')
                            ->label('')
                            ->statePath('masterSeedCatalogs')
                            ->addActionLabel('Add Variety')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable()
                            ->columnWidths([
                                'variety_selection' => '70%',
                                'percentage' => '30%',
                            ])
                            ->extraAttributes([
                                'style' => 'overflow: visible;',
                                'class' => 'relative z-10'
                            ])
                            ->schema([
                                Forms\Components\Select::make('variety_selection')
                                    ->label('Variety')
                                    ->options(function () {
                                        $options = [];
                                        
                                        // Get all active master seed catalogs with their cultivars
                                        $catalogs = \App\Models\MasterSeedCatalog::where('is_active', true)
                                            ->with('activeCultivars')
                                            ->orderBy('common_name')
                                            ->get();
                                        
                                        foreach ($catalogs as $catalog) {
                                            // Use the already loaded active cultivars
                                            $cultivars = $catalog->activeCultivars;
                                            
                                            if ($cultivars->isNotEmpty()) {
                                                // Add each cultivar as a separate option
                                                foreach ($cultivars as $cultivar) {
                                                    $key = $catalog->id . '|' . $cultivar->cultivar_name;
                                                    $label = $catalog->common_name . ' (' . $cultivar->cultivar_name . ')';
                                                    $options[$key] = $label;
                                                }
                                            } else {
                                                // If no cultivars, add the catalog with default cultivar
                                                $key = $catalog->id . '|Default';
                                                $label = $catalog->common_name . ' (Default)';
                                                $options[$key] = $label;
                                            }
                                        }
                                        
                                        return $options;
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            // Parse the composite key
                                            [$catalogId, $cultivar] = explode('|', $state);
                                            $set('master_seed_catalog_id', $catalogId);
                                            $set('cultivar', $cultivar);
                                        }
                                    })
                                    ->dehydrated(false) // Don't save this field directly
                                    ->searchable()
                                    ->required()
                                    ->extraAttributes([
                                        'style' => 'position: relative; z-index: 50;'
                                    ]),
                                
                                // Hidden fields to store the actual values
                                Forms\Components\Hidden::make('master_seed_catalog_id'),
                                Forms\Components\Hidden::make('cultivar'),
                                
                                Forms\Components\TextInput::make('percentage')
                                    ->label('Percentage (%)')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->maxValue(100)
                                    ->required()
                                    ->default(25)
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->inputMode('decimal')
                                    ->reactive(),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                                // Remove the variety_selection field as it's not part of the database
                                unset($data['variety_selection']);
                                
                                // Ensure we have the required fields
                                if (!isset($data['master_seed_catalog_id']) || !isset($data['percentage'])) {
                                    throw new \Exception('Missing required fields');
                                }
                                
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data) {
                                // Remove the variety_selection field as it's not part of the database
                                unset($data['variety_selection']);
                                
                                // Ensure we have the required fields
                                if (!isset($data['master_seed_catalog_id']) || !isset($data['percentage'])) {
                                    throw new \Exception('Missing required fields');
                                }
                                
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data) {
                                // When loading existing data, create the composite key for the select
                                if (isset($data['master_seed_catalog_id']) && isset($data['cultivar'])) {
                                    $data['variety_selection'] = $data['master_seed_catalog_id'] . '|' . $data['cultivar'];
                                }
                                // Ensure percentage is properly cast
                                if (isset($data['percentage'])) {
                                    $data['percentage'] = floatval($data['percentage']);
                                }
                                return $data;
                            })
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (ProductMix $record): string => ProductMixResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('components_summary')
                    ->label('Mix Components')
                    ->html()
                    ->getStateUsing(function (ProductMix $record): string {
                        $components = $record->masterSeedCatalogs()
                            ->withPivot('percentage', 'cultivar')
                            ->get()
                            ->map(fn ($catalog) => 
                                "<span class='inline-flex items-center px-2 py-1 mr-1 mb-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full dark:bg-gray-700 dark:text-gray-300'>" .
                                "{$catalog->common_name}" . 
                                ($catalog->pivot->cultivar ? " ({$catalog->pivot->cultivar})" : "") .
                                " - " . number_format($catalog->pivot->percentage, 2) . "%" .
                                "</span>"
                            )
                            ->join('');
                        
                        return $components ?: '<span class="text-gray-400">No components</span>';
                    })
                    ->searchable(false)
                    ->sortable(false),
                    
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Used in Products')
                    ->getStateUsing(fn (ProductMix $record): string => 
                        $record->products()->count() . ' product(s)'
                    )
                    ->badge()
                    ->color(fn (ProductMix $record): string => 
                        $record->products()->count() > 0 ? 'success' : 'gray'
                    )
                    ->sortable(false),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All mixes')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\Filter::make('unused')
                    ->label('Unused Mixes')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('products')),
                Tables\Filters\Filter::make('incomplete')
                    ->label('Incomplete Mixes')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('masterSeedCatalogs')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit mix'),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->tooltip('Create a copy of this mix')
                    ->action(function (ProductMix $record) {
                        $newMix = $record->replicate();
                        $newMix->name = $record->name . ' (Copy)';
                        $newMix->save();
                        
                        // Copy the seed varieties
                        foreach ($record->masterSeedCatalogs as $catalog) {
                            $newMix->masterSeedCatalogs()->attach($catalog->id, [
                                'percentage' => $catalog->pivot->percentage,
                                'cultivar' => $catalog->pivot->cultivar,
                            ]);
                        }
                        
                        redirect(static::getUrl('edit', ['record' => $newMix]));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete mix')
                    ->before(function (ProductMix $record) {
                        if ($record->products()->count() > 0) {
                            throw new \Exception('Cannot delete mix that is used by products.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductMixes::route('/'),
            'create' => Pages\CreateProductMix::route('/create'),
            'edit' => Pages\EditProductMix::route('/{record}'),
        ];
    }
} 