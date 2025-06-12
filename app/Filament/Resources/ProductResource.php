<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Component;
use App\Http\Livewire\ProductPriceCalculator;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;

class ProductResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Products';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getSinglePageFormSchema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('default_photo')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('variety_type')
                    ->label('Type')
                    ->getStateUsing(function ($record): string {
                        if ($record->seed_entry_id) {
                            return 'Single: ' . ($record->seedEntry->cultivar_name ?? 'Unknown');
                        } elseif ($record->product_mix_id) {
                            return 'Mix: ' . ($record->productMix->name ?? 'Unknown');
                        }
                        return 'None';
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible_in_store')
                    ->label('In Store')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('available_packaging')
                    ->label('Packaging')
                    ->html()
                    ->getStateUsing(function ($record): string {
                        $packaging = $record->priceVariations()
                            ->whereNotNull('packaging_type_id')
                            ->with('packagingType')
                            ->get()
                            ->pluck('packagingType.display_name')
                            ->unique()
                            ->take(3);
                        
                        if ($packaging->isEmpty()) {
                            return '<span class="text-gray-400">No packaging</span>';
                        }
                        
                        $badges = $packaging->map(function ($name) {
                            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . $name . '</span>';
                        })->join(' ');
                        
                        $total = $record->priceVariations()->whereNotNull('packaging_type_id')->count();
                        if ($total > 3) {
                            $badges .= ' <span class="text-xs text-gray-500">+' . ($total - 3) . ' more</span>';
                        }
                        
                        return $badges;
                    })
                    ->searchable(false)
                    ->sortable(false),
                ...static::getTimestampColumns(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('variety_type')
                    ->label('Product Type')
                    ->options([
                        'single' => 'Single Variety',
                        'mix' => 'Product Mix',
                        'none' => 'No Variety Assigned',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['value']) {
                            'single' => $query->whereNotNull('seed_entry_id'),
                            'mix' => $query->whereNotNull('product_mix_id'),
                            'none' => $query->whereNull('seed_entry_id')->whereNull('product_mix_id'),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\TernaryFilter::make('is_visible_in_store')
                    ->label('Visible in Store'),
            ])
            ->actions(static::getDefaultTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ...static::getDefaultBulkActions(),
                    Tables\Actions\BulkAction::make('show_in_store')
                        ->label('Show in Store')
                        ->icon('heroicon-o-eye')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_visible_in_store' => true]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('hide_from_store')
                        ->label('Hide from Store')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_visible_in_store' => false]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceVariationsRelationManager::class,
        ];
    }

    /**
     * Get the panels that should be displayed for viewing a record.
     */
    public static function getPanels(): array
    {
        try {
            \Illuminate\Support\Facades\Log::info('ProductResource: getPanels method called');
            
            return [
                'price_variations' => Forms\Components\Section::make('Price Variations')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('base_price_display')
                                    ->label('Default Price')
                                    ->content(function ($record) {
                                        $variation = $record->defaultPriceVariation();
                                        return $variation 
                                            ? '$' . number_format($variation->price, 2) . ' (' . $variation->name . ')'
                                            : '$' . number_format($record->base_price ?? 0, 2);
                                    }),
                                Forms\Components\Placeholder::make('variations_count')
                                    ->label('Price Variations')
                                    ->content(function ($record) {
                                        $count = $record->priceVariations()->count();
                                        $activeCount = $record->priceVariations()->where('is_active', true)->count();
                                        return "{$activeCount} active / {$count} total";
                                    }),
                            ]),
                        Forms\Components\Placeholder::make('variations_info')
                            ->content(function ($record) {
                                $priceTypes = ['Default', 'Wholesale', 'Bulk', 'Special'];
                                $content = "Price variations allow you to set different prices based on customer type or purchase unit.";
                                
                                $missingTypes = [];
                                foreach ($priceTypes as $type) {
                                    if (!$record->priceVariations()->where('name', $type)->exists()) {
                                        $missingTypes[] = $type;
                                    }
                                }
                                
                                if (!empty($missingTypes)) {
                                    $content .= "<br><br>Standard pricing types not yet created: <span class='text-primary-500'>" . implode(', ', $missingTypes) . "</span>";
                                }
                                
                                return $content;
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'prose']),
                        Forms\Components\ViewField::make('price_variations_panel')
                            ->view('filament.resources.product-resource.partials.price-variations')
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
                
                'variety_info' => Forms\Components\Section::make('Variety Information')
                    ->schema([
                        // Single Variety Section
                        Forms\Components\Group::make([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('variety_name')
                                        ->label('Variety Name')
                                        ->content(fn ($record) => $record->seedEntry->cultivar_name ?? 'Unknown'),
                                    Forms\Components\Placeholder::make('common_names')
                                        ->label('Common Names')
                                        ->content(fn ($record) => $record->seedEntry->common_names ?? 'N/A'),
                                ]),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('days_to_maturity')
                                        ->label('Days to Maturity')
                                        ->content(function ($record) {
                                            $recipe = $record->seedEntry->recipes()->first();
                                            return $recipe ? $recipe->days_to_maturity . ' days' : 'No recipe';
                                        }),
                                    Forms\Components\Placeholder::make('seed_inventory')
                                        ->label('Seed Stock')
                                        ->content(function ($record) {
                                            $consumable = $record->seedEntry->consumables()
                                                ->where('type', 'seed')
                                                ->first();
                                            if ($consumable) {
                                                $available = $consumable->initial_stock - $consumable->consumed_quantity;
                                                return number_format($available, 2) . ' ' . $consumable->unit;
                                            }
                                            return 'No inventory';
                                        }),
                                    Forms\Components\Placeholder::make('supplier')
                                        ->label('Primary Supplier')
                                        ->content(function ($record) {
                                            $consumable = $record->seedEntry->consumables()
                                                ->where('type', 'seed')
                                                ->with('supplier')
                                                ->first();
                                            return $consumable?->supplier?->name ?? 'N/A';
                                        }),
                                ]),
                        ])
                        ->visible(fn ($record) => $record->seed_entry_id !== null),
                        
                        // Product Mix Section
                        Forms\Components\Group::make([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('mix_name')
                                        ->label('Mix Name')
                                        ->content(fn ($record) => $record->productMix->name ?? 'Unknown'),
                                    Forms\Components\Placeholder::make('variety_count')
                                        ->label('Number of Varieties')
                                        ->content(fn ($record) => $record->productMix->seedEntries->count() ?? 0),
                                ]),
                            Forms\Components\Placeholder::make('varieties')
                                ->label('Varieties in Mix')
                                ->content(function ($record) {
                                    $varieties = $record->productMix->seedEntries;
                                    if ($varieties->isEmpty()) {
                                        return 'No varieties in this mix';
                                    }
                                    
                                    $content = '<ul class="list-disc list-inside space-y-1">';
                                    foreach ($varieties as $variety) {
                                        $percentage = $variety->pivot->percentage ?? 0;
                                        $content .= "<li><strong>{$variety->cultivar_name}</strong> ({$percentage}%)</li>";
                                    }
                                    $content .= '</ul>';
                                    
                                    return $content;
                                })
                                ->extraAttributes(['class' => 'prose'])
                                ->columnSpanFull(),
                        ])
                        ->visible(fn ($record) => $record->product_mix_id !== null),
                        
                        // No Variety Assigned Message
                        Forms\Components\Placeholder::make('no_variety')
                            ->label('')
                            ->content('This product is not linked to any variety or mix. Consider assigning one for better inventory and planting plan management.')
                            ->extraAttributes(['class' => 'text-warning-600'])
                            ->visible(fn ($record) => $record->seed_entry_id === null && $record->product_mix_id === null),
                    ])
                    ->hidden(function ($record) {
                        return $record->seed_entry_id === null && $record->product_mix_id === null;
                    })
                    ->collapsible()
                    ->columnSpanFull(),
            ];
        } catch (\Throwable $e) {
            \App\Services\DebugService::logError($e, 'ProductResource::getPanels');
            
            // Return a minimal panel set that won't cause errors
            return [
                'debug' => Forms\Components\Section::make('Debug Information')
                    ->schema([
                        Forms\Components\Placeholder::make('error')
                            ->label('Error')
                            ->content('An error occurred loading panels: ' . $e->getMessage()),
                    ]),
            ];
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
    /**
     * Get the single-page form schema
     */
    public static function getSinglePageFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Product Information')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('sku')
                                ->label('Product SKU')
                                ->maxLength(255)
                                ->helperText('Optional unique identifier'),
                        ]),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('category_id')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('description')
                                        ->maxLength(65535),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ]),
                            Forms\Components\Select::make('master_seed_catalog_id')
                                ->label('Single Variety')
                                ->options(function () {
                                    // Get master catalog entries that have seed inventory
                                    return \App\Models\MasterSeedCatalog::whereHas('consumables', function ($query) {
                                        $query->where('type', 'seed')
                                            ->where('is_active', true)
                                            ->whereRaw('(total_quantity - consumed_quantity) > 0');
                                    })
                                    ->where('is_active', true)
                                    ->orderBy('common_name', 'asc')
                                    ->get()
                                    ->mapWithKeys(function ($catalog) {
                                        $cultivars = is_array($catalog->cultivars) ? $catalog->cultivars : [];
                                        $cultivarName = !empty($cultivars) ? $cultivars[0] : 'No cultivar';
                                        
                                        return [$catalog->id => $catalog->common_name . ' (' . $cultivarName . ')'];
                                    })
                                    ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->disabled(fn (Forms\Get $get): bool => !empty($get('product_mix_id')))
                                ->helperText(fn (Forms\Get $get): string => 
                                    !empty($get('product_mix_id')) 
                                        ? 'Disabled: Product already has a mix assigned' 
                                        : 'Select variety from master catalog with available inventory'
                                ),
                            Forms\Components\Select::make('product_mix_id')
                                ->label('Product Mix')
                                ->relationship('productMix', 'name')
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->disabled(fn (Forms\Get $get): bool => !empty($get('seed_entry_id')))
                                ->helperText(fn (Forms\Get $get): string => 
                                    !empty($get('seed_entry_id')) 
                                        ? 'Disabled: Product already has a single variety assigned' 
                                        : 'Select for multi-variety products'
                                ),
                            Forms\Components\FileUpload::make('photo')
                                ->label('Primary Photo')
                                ->image()
                                ->directory('product-photos')
                                ->maxSize(5120)
                                ->imageResizeTargetWidth('800')
                                ->imageResizeTargetHeight('800')
                                ->disk('public'),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Toggle::make('active')
                                ->label('Active')
                                ->default(true),
                            Forms\Components\Toggle::make('is_visible_in_store')
                                ->label('Visible in Store')
                                ->default(true),
                        ]),
                ])
                ->columns(1),
            
            Forms\Components\Section::make('Price Variations')
                ->description('Select global templates to apply to this product, or create custom variations.')
                ->schema([
                    static::getPriceVariationSelectionField(),
                ])
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    /**
     * Get the price variation selection field using datatable
     */
    public static function getPriceVariationSelectionField(): Forms\Components\Component
    {
        return Forms\Components\ViewField::make('price_variations_selector')
            ->view('filament.forms.price-variations-selector')
            ->afterStateHydrated(function (Forms\Components\ViewField $component, $state) {
                $component->state([
                    'selected_templates' => [],
                    'custom_variations' => [],
                ]);
            });
    }

    /**
     * Get the form schema for the wizard steps
     */
    public static function getFormSchema($livewire): array
    {
        return [
            Step::make('Basic Information')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('sku')
                        ->label('Product SKU')
                        ->maxLength(255)
                        ->helperText('Optional unique identifier for this product'),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->maxLength(65535),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                            return $action
                                ->modalHeading('Create category')
                                ->modalSubmitActionLabel('Create category')
                                ->modalWidth('lg');
                        }),
                    Forms\Components\Select::make('product_mix_id')
                        ->label('Product Mix')
                        ->relationship('productMix', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Mix Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(3),
                            Forms\Components\Repeater::make('components')
                                ->label('Mix Components')
                                ->schema([
                                    Forms\Components\Select::make('seed_entry_id')
                                        ->label('Variety')
                                        ->options(\App\Models\SeedEntry::where('is_active', true)->pluck('cultivar_name', 'id'))
                                        ->searchable()
                                        ->required(),
                                    Forms\Components\TextInput::make('percentage')
                                        ->label('Percentage (%)')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(100)
                                        ->required()
                                        ->default(25)
                                        ->suffix('%'),
                                ])
                                ->columns(2)
                                ->defaultItems(2)
                                ->addActionLabel('Add Variety')
                                ->reorderableWithButtons()
                                ->helperText('Each variety\'s percentage should add up to 100%'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            // Create the ProductMix
                            $mix = \App\Models\ProductMix::create([
                                'name' => $data['name'],
                                'description' => $data['description'] ?? null,
                                'is_active' => $data['is_active'] ?? true,
                            ]);
                            
                            // Attach the components
                            if (isset($data['components']) && is_array($data['components'])) {
                                foreach ($data['components'] as $component) {
                                    if (isset($component['seed_entry_id']) && isset($component['percentage'])) {
                                        $mix->seedEntries()->attach($component['seed_entry_id'], [
                                            'percentage' => $component['percentage'],
                                        ]);
                                    }
                                }
                            }
                            
                            return $mix->id;
                        })
                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                            return $action
                                ->modalHeading('Create Product Mix')
                                ->modalSubmitActionLabel('Create Mix')
                                ->modalWidth('2xl');
                        })
                        ->helperText('Select an existing mix or create a new one if this product uses multiple seed varieties.'),
                    Toggle::make('active')
                        ->label('Active')
                        ->default(true),
                    Toggle::make('is_visible_in_store')
                        ->label('Visible in Store')
                        ->default(true)
                        ->helperText('Whether this product is visible to customers in the online store'),
                ])
                ->columns(3),
            Step::make('Pricing & Variations')
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Forms\Components\Section::make('Price Variations')
                        ->description('Create flexible pricing variations for different customer types, units, weights, and packaging options.')
                        ->schema([
                            Forms\Components\Repeater::make('priceVariations')
                                ->relationship('priceVariations')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Variation Name')
                                                ->required()
                                                ->placeholder('e.g., Retail, Wholesale, 4oz Container')
                                                ->maxLength(255),
                                            Forms\Components\Select::make('packaging_type_id')
                                                ->label('Packaging')
                                                ->relationship('packagingType', 'name')
                                                ->getOptionLabelFromRecordUsing(fn (\App\Models\PackagingType $record): string => $record->display_name)
                                                ->searchable()
                                                ->preload()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    if ($state) {
                                                        $packaging = \App\Models\PackagingType::find($state);
                                                        if ($packaging && empty($get('name'))) {
                                                            $set('name', $packaging->display_name);
                                                        }
                                                    }
                                                })
                                                ->helperText('Select the container/packaging type'),
                                            Forms\Components\TextInput::make('sku')
                                                ->label('SKU')
                                                ->placeholder('Optional product SKU')
                                                ->maxLength(100),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('fill_weight_grams')
                                                ->label('Product Fill Weight (grams)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->placeholder('e.g., 113.4')
                                                ->helperText('How much product (in grams) goes into this packaging')
                                                ->suffix('g'),
                                            Forms\Components\TextInput::make('price')
                                                ->label('Price')
                                                ->numeric()
                                                ->prefix('$')
                                                ->required()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->reactive(),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('is_default')
                                                ->label('Default Price')
                                                ->helperText('One variation must be default'),
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                        ]),
                                ])
                                ->defaultItems(1)
                                ->addActionLabel('Add Price Variation')
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => 
                                    $state['name'] ?? 'New Variation'
                                )
                                ->columnSpanFull(),
                        ]),
                    
                    Forms\Components\Section::make('Quick Setup')
                        ->description('Use these templates to quickly create common pricing variations.')
                        ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('add_retail_wholesale')
                                    ->label('Add Retail + Wholesale')
                                    ->icon('heroicon-o-plus')
                                    ->color('primary')
                                    ->action(function ($livewire) {
                                        $variations = $livewire->data['priceVariations'] ?? [];
                                        $variations[] = ['name' => 'Retail', 'price' => 0, 'is_default' => true, 'is_active' => true];
                                        $variations[] = ['name' => 'Wholesale', 'price' => 0, 'is_default' => false, 'is_active' => true];
                                        $livewire->data['priceVariations'] = $variations;
                                    }),
                                Forms\Components\Actions\Action::make('add_packaging_based')
                                    ->label('Add Common Packaging Sizes')
                                    ->icon('heroicon-o-archive-box')
                                    ->color('success')
                                    ->action(function ($livewire) {
                                        $variations = $livewire->data['priceVariations'] ?? [];
                                        
                                        // Get common packaging types (16oz and 32oz clamshells)
                                        $packaging16oz = \App\Models\PackagingType::where('capacity_volume', 16)->where('volume_unit', 'oz')->first();
                                        $packaging32oz = \App\Models\PackagingType::where('capacity_volume', 32)->where('volume_unit', 'oz')->first();
                                        
                                        if ($packaging16oz) {
                                            $variations[] = [
                                                'name' => $packaging16oz->display_name,
                                                'packaging_type_id' => $packaging16oz->id,
                                                'price' => 0,
                                                'is_default' => empty($variations),
                                                'is_active' => true
                                            ];
                                        }
                                        
                                        if ($packaging32oz) {
                                            $variations[] = [
                                                'name' => $packaging32oz->display_name,
                                                'packaging_type_id' => $packaging32oz->id,
                                                'price' => 0,
                                                'is_default' => false,
                                                'is_active' => true
                                            ];
                                        }
                                        
                                        $livewire->data['priceVariations'] = $variations;
                                    }),
                            ])
                        ])
                        ->visible(fn ($livewire) => empty($livewire->data['priceVariations'] ?? [])),
                    
                    Forms\Components\Section::make('Pricing Preview')
                        ->description('Live preview of your pricing structure.')
                        ->schema([
                            \App\Forms\Components\PriceVariationsPreview::make('price_preview')
                                ->label('')
                                ->reactive(),
                        ])
                        ->visible(fn ($livewire) => !empty($livewire->data['priceVariations'] ?? [])),
                        
                    Forms\Components\ViewField::make('price_calculator')
                        ->view('livewire.product-price-calculator')
                        ->visible(function ($livewire) {
                            return $livewire->record !== null;
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Step::make('Product Photos')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('new_photos')
                        ->label('Photos')
                        ->multiple()
                        ->image()
                        ->directory('product-photos')
                        ->maxSize(5120)
                        ->imageResizeTargetWidth('1200')
                        ->imageResizeTargetHeight('1200')
                        ->disk('public')
                        ->helperText('Upload one or more photos. The first photo will be set as default.')
                        ->afterStateUpdated(function ($state, $livewire) {
                            // Skip if no photos were uploaded
                            if (empty($state)) {
                                return;
                            }
                            
                            $product = $livewire->record;
                            if (!$product) {
                                // Save the uploaded photos to be used after record creation
                                $livewire->temporaryPhotos = $state;
                                return;
                            }
                            
                            // Get next order value
                            $maxOrder = $product->photos()->max('order');
                            $maxOrder = is_numeric($maxOrder) ? (int)$maxOrder : 0;
                            
                            // Check if we have any default photos
                            $hasDefault = $product->photos()->where('is_default', true)->exists();
                            
                            // Process each uploaded photo
                            foreach ($state as $index => $path) {
                                // Set the first one as default if no default exists
                                $isDefault = ($index === 0 && !$hasDefault);
                                
                                $photo = $product->photos()->create([
                                    'photo' => $path,
                                    'is_default' => $isDefault,
                                    'order' => $maxOrder + $index + 1,
                                ]);
                                
                                // If this is the default, ensure it's properly set
                                if ($isDefault) {
                                    $photo->setAsDefault();
                                }
                            }
                            
                            // Clear the upload field
                            $livewire->form->fill([
                                'new_photos' => null,
                            ]);
                            
                            // Refresh the page to show the new photos if we're on the edit page
                            if ($livewire->record) {
                                $livewire->redirect(ProductResource::getUrl('edit', ['record' => $livewire->record]));
                            }
                        }),
                ]),
        ];
    }
} 