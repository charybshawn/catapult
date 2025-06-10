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
use Filament\Forms\Components\Repeater;

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
                Forms\Components\Section::make('Seed Entry Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('common_name')
                                    ->label('Common Name')
                                    ->options(function () {
                                        return \App\Models\SeedEntry::whereNotNull('common_name')
                                            ->where('common_name', '<>', '')
                                            ->distinct()
                                            ->orderBy('common_name')
                                            ->pluck('common_name', 'common_name')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->allowHtml()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('common_name')
                                            ->label('New Common Name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['common_name'];
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // When common name changes, filter cultivar options
                                        $set('cultivar_name', null); // Reset cultivar selection
                                    }),
                                Forms\Components\Select::make('cultivar_name')
                                    ->required()
                                    ->label('Cultivar Name')
                                    ->options(function (Forms\Get $get) {
                                        $commonName = $get('common_name');
                                        
                                        $query = \App\Models\SeedEntry::whereNotNull('cultivar_name')
                                            ->where('cultivar_name', '<>', '');
                                            
                                        if ($commonName) {
                                            $query->where('common_name', $commonName);
                                        }
                                        
                                        return $query->distinct()
                                            ->orderBy('cultivar_name')
                                            ->pluck('cultivar_name', 'cultivar_name')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->allowHtml()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('cultivar_name')
                                            ->label('New Cultivar Name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['cultivar_name'];
                                    })
                                    ->placeholder(function (Forms\Get $get) {
                                        $commonName = $get('common_name');
                                        return $commonName ? "Select or create cultivar for {$commonName}" : 'Select common name first';
                                    })
                                    ->disabled(fn (Forms\Get $get): bool => empty($get('common_name')))
                                    ->helperText('Cultivar options will filter based on your common name selection'),
                            ]),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('website')
                                    ->url()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(65535),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier_product_title')
                                    ->maxLength(255)
                                    ->label('Product Title'),
                                Forms\Components\TextInput::make('supplier_product_url')
                                    ->url()
                                    ->maxLength(255)
                                    ->label('Product URL'),
                            ]),
                        Forms\Components\TextInput::make('image_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Image URL'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Pricing Variations')
                    ->schema([
                        Repeater::make('variations')
                            ->relationship()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['size_description']) && isset($state['current_price']) 
                                    ? ($state['size_description'] . ' - $' . number_format($state['current_price'], 2))
                                    : (isset($state['size_description']) ? $state['size_description'] : 'New Variation')
                            )
                            ->collapsed()
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('size_description')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Size Description')
                                            ->placeholder('e.g., 25 grams, 1 kg, 5 lb bag'),
                                        Forms\Components\TextInput::make('weight_kg')
                                            ->numeric()
                                            ->step('0.0001')
                                            ->label('Weight (kg)')
                                            ->placeholder('0.025')
                                            ->helperText('Common conversions: 25g = 0.025kg, 100g = 0.1kg, 1lb = 0.454kg')
                                            ->live(),
                                        Forms\Components\TextInput::make('sku')
                                            ->maxLength(255)
                                            ->label('SKU')
                                            ->placeholder('Optional'),
                                    ]),
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_price')
                                            ->required()
                                            ->numeric()
                                            ->prefix('$')
                                            ->label('Current Price')
                                            ->live(),
                                        Forms\Components\Select::make('currency')
                                            ->options([
                                                'USD' => 'USD',
                                                'CAD' => 'CAD',
                                                'EUR' => 'EUR',
                                                'GBP' => 'GBP',
                                            ])
                                            ->default('CAD')
                                            ->required(),
                                        Forms\Components\Toggle::make('is_in_stock')
                                            ->label('In Stock')
                                            ->default(true)
                                            ->inline(false),
                                        Forms\Components\Placeholder::make('price_per_kg_display')
                                            ->label('Price per kg')
                                            ->content(function (Forms\Get $get): string {
                                                $price = $get('current_price');
                                                $weight = $get('weight_kg');
                                                $currency = $get('currency') ?? 'CAD';
                                                
                                                if ($price && $weight && $weight > 0) {
                                                    $pricePerKg = $price / $weight;
                                                    return $currency . ' $' . number_format($pricePerKg, 2);
                                                }
                                                
                                                return 'Enter price and weight';
                                            })
                                            ->live(),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('original_weight_value')
                                            ->numeric()
                                            ->label('Original Weight Value')
                                            ->placeholder('25'),
                                        Forms\Components\TextInput::make('original_weight_unit')
                                            ->maxLength(255)
                                            ->label('Original Weight Unit')
                                            ->placeholder('grams'),
                                    ]),
                            ])
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->addActionLabel('Add Another Variation')
                            ->defaultItems(1)
                            ->columnSpanFull()
                            ->live(),
                    ])
                    ->collapsible()
                    ->description('Add different sizes and pricing options for this seed entry. You can add as many variations as needed.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('common_name')
                    ->label('Common Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cultivar_name')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier_product_title')
                    ->label('Product Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supplier_product_url')
                    ->label('Product URL')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('variations_count')
                    ->counts('variations')
                    ->label('Variations')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Stock Status')
                    ->getStateUsing(function (SeedEntry $record): string {
                        $variations = $record->variations;
                        
                        if ($variations->isEmpty()) {
                            return 'No Variations';
                        }
                        
                        $inStockCount = $variations->where('is_in_stock', true)->count();
                        $totalCount = $variations->count();
                        
                        if ($inStockCount === 0) {
                            return 'All Out of Stock';
                        } elseif ($inStockCount === $totalCount) {
                            return 'All In Stock';
                        } else {
                            return "Partial ({$inStockCount}/{$totalCount} in stock)";
                        }
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'All In Stock') => 'success',
                        str_contains($state, 'Partial') => 'warning',
                        str_contains($state, 'All Out of Stock') => 'danger',
                        str_contains($state, 'No Variations') => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(function (SeedEntry $record): string {
                        $variations = $record->variations;
                        
                        if ($variations->isEmpty()) {
                            return 'This seed entry has no price variations defined.';
                        }
                        
                        $inStock = $variations->where('is_in_stock', true);
                        $outOfStock = $variations->where('is_in_stock', false);
                        
                        $tooltip = "Total variations: {$variations->count()}";
                        
                        if ($inStock->count() > 0) {
                            $tooltip .= "\n\nIn Stock:\n" . $inStock->pluck('size_description')->join(', ');
                        }
                        
                        if ($outOfStock->count() > 0) {
                            $tooltip .= "\n\nOut of Stock:\n" . $outOfStock->pluck('size_description')->join(', ');
                        }
                        
                        return $tooltip;
                    })
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('usage_status')
                    ->label('Usage Status')
                    ->getStateUsing(function (SeedEntry $record): string {
                        $issues = self::checkSeedEntryDeletionSafety($record);
                        if (empty($issues)) {
                            return 'Available';
                        }
                        return 'In Use (' . count($issues) . ' dependencies)';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Available') => 'success',
                        str_contains($state, 'In Use') => 'warning',
                        default => 'gray',
                    })
                    ->tooltip(function (SeedEntry $record): string {
                        $issues = self::checkSeedEntryDeletionSafety($record);
                        if (empty($issues)) {
                            return 'This seed entry is not being used and can be safely deleted.';
                        }
                        return 'This seed entry is in use: ' . implode('; ', $issues);
                    })
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('common_name')
                    ->options(function () {
                        return \App\Models\SeedEntry::whereNotNull('common_name')
                            ->where('common_name', '<>', '')
                            ->distinct()
                            ->orderBy('common_name')
                            ->pluck('common_name', 'common_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Common Name'),
                Tables\Filters\SelectFilter::make('cultivar_name')
                    ->options(function () {
                        return \App\Models\SeedEntry::whereNotNull('cultivar_name')
                            ->where('cultivar_name', '<>', '')
                            ->distinct()
                            ->orderBy('cultivar_name')
                            ->pluck('cultivar_name', 'cultivar_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Cultivar'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('usage_status')
                    ->label('Usage Status')
                    ->options([
                        'available' => 'Available for Deletion',
                        'in_use' => 'In Use (Cannot Delete)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            if ($data['value'] === 'available') {
                                // Find seed entries that are not in use
                                $query->whereDoesntHave('recipes')
                                    ->whereDoesntHave('consumables', function($query) {
                                        $query->where('is_active', true);
                                    });
                            } elseif ($data['value'] === 'in_use') {
                                // Find seed entries that are in use
                                $query->where(function ($query) {
                                    $query->whereHas('recipes')
                                        ->orWhereHas('consumables', function($query) {
                                            $query->where('is_active', true);
                                        });
                                });
                            }
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'all_in_stock' => 'All In Stock',
                        'partial_stock' => 'Partial Stock',
                        'all_out_of_stock' => 'All Out of Stock',
                        'no_variations' => 'No Variations',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            switch ($data['value']) {
                                case 'all_in_stock':
                                    // Entries where all variations are in stock
                                    $query->whereHas('variations')
                                        ->whereDoesntHave('variations', function($query) {
                                            $query->where('is_in_stock', false);
                                        });
                                    break;
                                case 'partial_stock':
                                    // Entries with mix of in stock and out of stock
                                    $query->whereHas('variations', function($query) {
                                        $query->where('is_in_stock', true);
                                    })
                                    ->whereHas('variations', function($query) {
                                        $query->where('is_in_stock', false);
                                    });
                                    break;
                                case 'all_out_of_stock':
                                    // Entries where all variations are out of stock
                                    $query->whereHas('variations')
                                        ->whereDoesntHave('variations', function($query) {
                                            $query->where('is_in_stock', true);
                                        });
                                    break;
                                case 'no_variations':
                                    // Entries with no variations
                                    $query->whereDoesntHave('variations');
                                    break;
                            }
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Seed Entry')
                    ->modalDescription('Are you sure you want to delete this seed entry?')
                    ->before(function (Tables\Actions\DeleteAction $action, SeedEntry $record) {
                        // Check for active relationships that would prevent deletion
                        $issues = self::checkSeedEntryDeletionSafety($record);
                        
                        if (!empty($issues)) {
                            // Cancel the action and show the issues
                            $action->cancel();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Delete Seed Entry')
                                ->body(
                                    'This seed entry cannot be deleted because it is actively being used:' . 
                                    '<br><br><strong>' . implode('</strong><br><strong>', $issues) . '</strong>' .
                                    '<br><br>Please remove these dependencies first, or consider deactivating the seed entry instead.'
                                )
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Seed Entry')
                    ->modalDescription('This will deactivate the seed entry, making it unavailable for new uses while preserving existing data.')
                    ->action(function (SeedEntry $record) {
                        $record->update(['is_active' => false]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Seed Entry Deactivated')
                            ->body("'{$record->common_name} - {$record->cultivar_name}' has been deactivated.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SeedEntry $record) => $record->is_active ?? true),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function (SeedEntry $record) {
                        $record->update(['is_active' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Seed Entry Activated')
                            ->body("'{$record->common_name} - {$record->cultivar_name}' has been activated.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SeedEntry $record) => !($record->is_active ?? true)),
                Tables\Actions\Action::make('visit_url')
                    ->label('Visit URL')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (SeedEntry $record) => $record->supplier_product_url)
                    ->openUrlInNewTab()
                    ->visible(fn (SeedEntry $record) => !empty($record->supplier_product_url)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Seed Entries')
                        ->modalDescription('Are you sure you want to delete the selected seed entries?')
                        ->before(function (Tables\Actions\DeleteBulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                            // Check each record for deletion safety
                            $protectedEntries = [];
                            $allIssues = [];
                            
                            foreach ($records as $record) {
                                $issues = self::checkSeedEntryDeletionSafety($record);
                                if (!empty($issues)) {
                                    $protectedEntries[] = $record->common_name . ' - ' . $record->cultivar_name;
                                    $allIssues = array_merge($allIssues, $issues);
                                }
                            }
                            
                            if (!empty($protectedEntries)) {
                                // Cancel the action and show the issues
                                $action->cancel();
                                
                                $entryList = implode(', ', $protectedEntries);
                                $issueList = array_unique($allIssues);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot Delete Some Seed Entries')
                                    ->body(
                                        'The following seed entries cannot be deleted because they are actively being used:' . 
                                        '<br><br><strong>' . $entryList . '</strong>' .
                                        '<br><br>Issues found:' .
                                        '<br><strong>' . implode('</strong><br><strong>', $issueList) . '</strong>' .
                                        '<br><br>Please remove these dependencies first, or consider deactivating the seed entries instead.'
                                    )
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
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
    
    /**
     * Check if a seed entry can be safely deleted
     * 
     * @param SeedEntry $seedEntry
     * @return array Array of issues preventing deletion (empty if safe to delete)
     */
    protected static function checkSeedEntryDeletionSafety(SeedEntry $seedEntry): array
    {
        $issues = [];
        
        // Check for recipes using this seed entry
        $recipesCount = \App\Models\Recipe::where('seed_entry_id', $seedEntry->id)->count();
        if ($recipesCount > 0) {
            // Check if any of these recipes have active crops
            $activeCropsCount = \App\Models\Crop::whereHas('recipe', function($query) use ($seedEntry) {
                $query->where('seed_entry_id', $seedEntry->id);
            })->where('current_stage', '!=', 'harvested')->count();
            
            if ($activeCropsCount > 0) {
                $issues[] = "{$activeCropsCount} active crops are using recipes with this seed entry";
            }
            
            $issues[] = "{$recipesCount} recipe(s) are using this seed entry";
        }
        
        // Check for consumables linked to this seed entry
        $consumablesCount = \App\Models\Consumable::where('seed_entry_id', $seedEntry->id)
            ->where('is_active', true)
            ->count();
        if ($consumablesCount > 0) {
            $issues[] = "{$consumablesCount} active consumable(s) are linked to this seed entry";
        }
        
        // Price history is not considered a blocking dependency since it's just historical data
        // and doesn't affect the ability to delete seed entries safely
        
        return $issues;
    }
} 