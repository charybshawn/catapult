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
use App\Forms\Components\SeedVariations;
use Filament\Notifications\Notification;

class SeedEntryResource extends Resource
{
    protected static ?string $model = SeedEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    
    protected static ?string $navigationLabel = 'Seeds';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Seed Identification')
                    ->description('Identify the seed type and variety. Both common name and cultivar are required.')
                    ->icon('heroicon-o-identification')
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
                    ]),
                
                Forms\Components\Section::make('Supplier Information')
                    ->description('Specify the supplier and their product details.')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
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
                                Forms\Components\TextInput::make('supplier_sku')
                                    ->maxLength(255)
                                    ->label('Supplier SKU')
                                    ->placeholder('e.g., BSL-001, BASIL-25G')
                                    ->helperText('Supplier\'s product code or identifier'),
                                Forms\Components\TextInput::make('url')
                                    ->url()
                                    ->maxLength(255)
                                    ->label('Product URL')
                                    ->placeholder('https://supplier.com/product-page')
                                    ->helperText('Link to supplier\'s product page'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Additional Details')
                    ->description('Optional information to enhance the seed entry.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('image_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Image URL')
                            ->placeholder('https://example.com/seed-image.jpg')
                            ->helperText('URL to product image'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->placeholder('Optional description of this seed variety...')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('organic, heirloom, fast-growing')
                            ->helperText('Add tags to categorize this seed')
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Seed Variations & Pricing')
                    ->description('Manage different sizes, weights, and pricing options for this seed entry.')
                    ->schema([
                        // Show different UI for create vs edit mode (following ProductResource pattern)
                        Forms\Components\Group::make([
                            // For create mode: show simple info message
                            Forms\Components\Placeholder::make('create_mode_info')
                                ->label('Price Variations')
                                ->content('Save the seed entry first, then you can add price variations for different sizes and weights.')
                                ->extraAttributes(['class' => 'text-sm text-gray-600'])
                                ->visible(function ($livewire) {
                                    // Check if we have a record - if not, we're in create mode
                                    $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                                    return !$record || !$record->exists;
                                }),
                                
                            // For edit mode: show the full variations management
                            Forms\Components\Group::make([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Placeholder::make('variations_count')
                                            ->label('Seed Variations')
                                            ->content(function ($record) {
                                                if (!$record) return '0 variations';
                                                $count = $record->variations()->count();
                                                $activeCount = $record->variations()->where('is_available', true)->count();
                                                return "{$activeCount} available / {$count} total";
                                            }),
                                        Forms\Components\Placeholder::make('default_variation_display')
                                            ->label('Primary Variation')
                                            ->content(function ($record) {
                                                if (!$record) return 'No variations yet';
                                                $defaultVariation = $record->variations()->first();
                                                return $defaultVariation 
                                                    ? $defaultVariation->size . ' - $' . number_format($defaultVariation->current_price, 2)
                                                    : 'No variations created';
                                            }),
                                    ]),
                                Forms\Components\Placeholder::make('variations_info')
                                    ->content(function ($record) {
                                        $content = "Seed variations allow you to offer different package sizes, weights, and prices for the same seed type.";
                                        
                                        if ($record) {
                                            $variationTypes = $record->variations()->pluck('size')->toArray();
                                            if (!empty($variationTypes)) {
                                                $content .= "<br><br>Current variations: <span class='text-primary-500'>" . implode(', ', $variationTypes) . "</span>";
                                            }
                                        }
                                        
                                        return $content;
                                    })
                                    ->columnSpanFull()
                                    ->extraAttributes(['class' => 'prose']),
                                Forms\Components\ViewField::make('seed_variations_panel')
                                    ->view('filament.resources.seed-entry-resource.partials.seed-variations')
                                    ->columnSpanFull(),
                            ])->visible(function ($livewire) {
                                // Check if we have a record - if so, we're in edit mode
                                $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                                return $record && $record->exists;
                            }),
                        ])
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
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
                Tables\Columns\TextColumn::make('supplier_sku')
                    ->label('Supplier SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('url')
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
                    ->url(fn (SeedEntry $record) => $record->url)
                    ->openUrlInNewTab()
                    ->visible(fn (SeedEntry $record) => !empty($record->url)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('import_to_master_catalog')
                        ->label('Import to Master Catalog')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Import to Master Seed Catalog')
                        ->modalDescription('This will create or update entries in the Master Seed Catalog based on the selected seed entries.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $imported = 0;
                            $updated = 0;
                            $errors = [];
                            
                            foreach ($records as $seedEntry) {
                                try {
                                    // Find existing master catalog entry by common name
                                    $masterCatalog = \App\Models\MasterSeedCatalog::where('common_name', $seedEntry->common_name)
                                        ->first();
                                    
                                    if ($masterCatalog) {
                                        // Update existing entry - add cultivar if not already present
                                        $cultivars = $masterCatalog->cultivars ?? [];
                                        
                                        // Check if cultivar already exists (case-insensitive)
                                        $cultivarExists = false;
                                        foreach ($cultivars as $existing) {
                                            if (strcasecmp(trim($existing), trim($seedEntry->cultivar_name)) === 0) {
                                                $cultivarExists = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$cultivarExists) {
                                            $cultivars[] = $seedEntry->cultivar_name;
                                            $masterCatalog->update([
                                                'cultivars' => array_values(array_unique($cultivars))
                                            ]);
                                            $updated++;
                                        }
                                    } else {
                                        // Create new master catalog entry
                                        \App\Models\MasterSeedCatalog::create([
                                            'common_name' => $seedEntry->common_name,
                                            'cultivars' => [$seedEntry->cultivar_name],
                                            'description' => $seedEntry->description,
                                            'is_active' => true,
                                        ]);
                                        $imported++;
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = "{$seedEntry->common_name} - {$seedEntry->cultivar_name}: " . $e->getMessage();
                                }
                            }
                            
                            // Show results
                            if ($imported > 0 || $updated > 0) {
                                $message = [];
                                if ($imported > 0) {
                                    $message[] = "{$imported} new master catalog entries created";
                                }
                                if ($updated > 0) {
                                    $message[] = "{$updated} existing entries updated with new cultivars";
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Import Successful')
                                    ->body(implode('<br>', $message))
                                    ->success()
                                    ->send();
                            }
                            
                            if (!empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some imports failed')
                                    ->body('Errors:<br>' . implode('<br>', array_slice($errors, 0, 5)) . 
                                           (count($errors) > 5 ? '<br>...and ' . (count($errors) - 5) . ' more errors' : ''))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
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
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('variations'))
            ->recordAction(Tables\Actions\EditAction::class);
    }

    public static function getRelations(): array
    {
        return [
            // Variations are now managed inline in the form using the custom component
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedEntries::route('/'),
            'create' => Pages\CreateSeedEntry::route('/create'),
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