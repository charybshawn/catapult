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
use Filament\Forms\Components\Wizard\Step;
use App\Http\Livewire\ProductPriceCalculator;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Traits\CsvExportAction;
use Illuminate\Support\Facades\Log;

class ProductResource extends BaseResource
{
    use CsvExportAction;
    
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'category',
                'masterSeedCatalog',
                'productMix',
                'priceVariations.packagingType'
            ]))
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
                        if ($record->master_seed_catalog_id) {
                            $catalog = $record->masterSeedCatalog;
                            $cultivar = !empty($catalog->cultivars) ? $catalog->cultivars[0] : 'Unknown';
                            return 'Single: ' . $catalog->common_name . ' (' . $cultivar . ')';
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
                        // Get only product-specific price variations with packaging
                        $productPackaging = $record->priceVariations()
                            ->whereNotNull('packaging_type_id')
                            ->with('packagingType')
                            ->get()
                            ->pluck('packagingType.display_name')
                            ->unique();
                        
                        // Only show actual product packaging, not potential templates
                        $packaging = $productPackaging;
                        
                        if ($packaging->isEmpty()) {
                            return '<span class="text-gray-400">No packaging</span>';
                        }
                        
                        // Create badges for actual product packaging
                        $badges = $packaging->map(function ($name) {
                            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . $name . '</span>';
                        })->join(' ');
                        
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
                            'single' => $query->whereNotNull('master_seed_catalog_id'),
                            'mix' => $query->whereNotNull('product_mix_id'),
                            'none' => $query->whereNull('master_seed_catalog_id')->whereNull('product_mix_id'),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\TernaryFilter::make('is_visible_in_store')
                    ->label('Visible in Store'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View record'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit record'),
                Tables\Actions\Action::make('clone')
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->tooltip('Clone this product')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Clone Product')
                    ->modalDescription('This will create a copy of the product with all its price variations and photos. Inventory will not be copied.')
                    ->modalSubmitActionLabel('Clone Product')
                    ->action(function (Product $record) {
                        try {
                            $newProduct = $record->cloneProduct();
                            
                            Notification::make()
                                ->title('Product Cloned Successfully')
                                ->body("Created: {$newProduct->name}")
                                ->success()
                                ->send();
                                
                            // Redirect to the edit page of the new product
                            return redirect()->to(ProductResource::getUrl('edit', ['record' => $newProduct]));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Clone Failed')
                                ->body('Failed to clone product: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete record')
                    ->before(function (Product $record) {
                        $deleteCheck = $record->canBeDeleted();
                        
                        if (!$deleteCheck['canDelete']) {
                            Notification::make()
                                ->title('Cannot Delete Product')
                                ->body("Product '{$record->name}' cannot be deleted:\n" . implode("\n", $deleteCheck['errors']))
                                ->danger()
                                ->send();
                            
                            // Cancel the deletion
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Check each record for inventory
                            foreach ($records as $record) {
                                $deleteCheck = $record->canBeDeleted();
                                
                                if (!$deleteCheck['canDelete']) {
                                    Notification::make()
                                        ->title('Cannot Delete Products')
                                        ->body("Product '{$record->name}' cannot be deleted:\n" . implode("\n", $deleteCheck['errors']) . "\n\nPlease resolve issues for all selected products first.")
                                        ->danger()
                                        ->send();
                                    
                                    // Cancel the deletion
                                    return false;
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('success'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        })
                        ->requiresConfirmation()
                        ->color('danger'),
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
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    /**
     * Define CSV export columns for Products - uses automatic detection from schema
     * Optionally add relationship columns manually
     */
    protected static function getCsvExportColumns(): array
    {
        // Get automatically detected columns from database schema
        $autoColumns = static::getColumnsFromSchema();
        
        // Add relationship columns
        return static::addRelationshipColumns($autoColumns, [
            'category' => ['name'],
            'masterSeedCatalog' => ['common_name', 'cultivars'],
            'productMix' => ['name'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['category', 'masterSeedCatalog', 'productMix'];
    }

    /**
     * Get the panels that should be displayed for viewing a record.
     */
    public static function getPanels(): array
    {
        try {
            Log::info('ProductResource: getPanels method called');
            
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
                                        ->content(function ($record) {
                                            $catalog = $record->masterSeedCatalog;
                                            if (!$catalog) return 'Unknown';
                                            $cultivar = !empty($catalog->cultivars) ? $catalog->cultivars[0] : 'No cultivar';
                                            return $catalog->common_name . ' (' . $cultivar . ')';
                                        }),
                                    Forms\Components\Placeholder::make('cultivars')
                                        ->label('All Cultivars')
                                        ->content(function ($record) {
                                            $catalog = $record->masterSeedCatalog;
                                            if (!$catalog || empty($catalog->cultivars)) return 'N/A';
                                            return implode(', ', $catalog->cultivars);
                                        }),
                                ]),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('category')
                                        ->label('Category')
                                        ->content(function ($record) {
                                            $catalog = $record->masterSeedCatalog;
                                            return $catalog->category ?? 'No category';
                                        }),
                                    Forms\Components\Placeholder::make('seed_inventory')
                                        ->label('Seed Stock')
                                        ->content(function ($record) {
                                            $catalog = $record->masterSeedCatalog;
                                            if (!$catalog) return 'No inventory';
                                            
                                            $consumables = $catalog->consumables()->whereHas('consumableType', function($query) {
                                                $query->where('code', 'seed');
                                            })->get();
                                            if ($consumables->isEmpty()) return 'No inventory';
                                            
                                            $totalAvailable = 0;
                                            $unit = '';
                                            foreach ($consumables as $consumable) {
                                                $available = $consumable->total_quantity - $consumable->consumed_quantity;
                                                $totalAvailable += $available;
                                                $unit = $consumable->unit;
                                            }
                                            
                                            return number_format($totalAvailable, 2) . ' ' . $unit;
                                        }),
                                    Forms\Components\Placeholder::make('supplier')
                                        ->label('Primary Supplier')
                                        ->content(function ($record) {
                                            $catalog = $record->masterSeedCatalog;
                                            if (!$catalog) return 'N/A';
                                            
                                            $consumable = $catalog->consumables()
                                                ->whereHas('consumableType', function($query) {
                                                    $query->where('code', 'seed');
                                                })
                                                ->with('supplier')
                                                ->first();
                                            return $consumable?->supplier?->name ?? 'N/A';
                                        }),
                                ]),
                        ])
                        ->visible(fn ($record) => $record->master_seed_catalog_id !== null),
                        
                        // Product Mix Section
                        Forms\Components\Group::make([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('mix_name')
                                        ->label('Mix Name')
                                        ->content(fn ($record) => $record->productMix->name ?? 'Unknown'),
                                    Forms\Components\Placeholder::make('variety_count')
                                        ->label('Number of Varieties')
                                        ->content(fn ($record) => $record->productMix->masterSeedCatalogs->count() ?? 0),
                                ]),
                            Forms\Components\Placeholder::make('varieties')
                                ->label('Varieties in Mix')
                                ->content(function ($record) {
                                    $varieties = $record->productMix->masterSeedCatalogs;
                                    if ($varieties->isEmpty()) {
                                        return 'No varieties in this mix';
                                    }
                                    
                                    $content = '<ul class="list-disc list-inside space-y-1">';
                                    foreach ($varieties as $catalog) {
                                        $percentage = $catalog->pivot->percentage ?? 0;
                                        $cultivar = $catalog->pivot->cultivar ?? '';
                                        
                                        $displayName = $catalog->common_name;
                                        if ($cultivar) {
                                            $displayName .= " ({$cultivar})";
                                        }
                                        
                                        $content .= "<li><strong>{$displayName}</strong> - {$percentage}%</li>";
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
                            ->visible(fn ($record) => $record->master_seed_catalog_id === null && $record->product_mix_id === null),
                    ])
                    ->hidden(function ($record) {
                        return $record->master_seed_catalog_id === null && $record->product_mix_id === null;
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
                                ->unique(
                                    table: 'products',
                                    column: 'name',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule) {
                                        return $rule->whereNull('deleted_at');
                                    }
                                )
                                ->validationMessages([
                                    'unique' => 'A product with this name already exists. Please choose a different name.',
                                ])
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('sku')
                                ->label('Product SKU')
                                ->maxLength(255)
                                ->unique(
                                    table: 'products',
                                    column: 'sku',
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule) {
                                        return $rule->whereNull('deleted_at');
                                    }
                                )
                                ->validationMessages([
                                    'unique' => 'This SKU is already in use. Please enter a different SKU.',
                                ])
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
                                    // Get all active master catalog entries
                                    return \App\Models\MasterSeedCatalog::where('is_active', true)
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
                                        : 'Select variety from master catalog'
                                ),
                            Forms\Components\Select::make('product_mix_id')
                                ->label('Product Mix')
                                ->relationship('productMix', 'name')
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->disabled(fn (Forms\Get $get): bool => !empty($get('master_seed_catalog_id')))
                                ->helperText(fn (Forms\Get $get): string => 
                                    !empty($get('master_seed_catalog_id')) 
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
            
            Forms\Components\Section::make('Pricing Strategy')
                ->description('Set retail prices in price variations, then configure wholesale discount here.')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('wholesale_discount_percentage')
                                ->label('Wholesale Discount %')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->default(25.00)
                                ->helperText('Percentage discount applied to retail prices for wholesale customers'),
                            Forms\Components\Placeholder::make('pricing_explanation')
                                ->label('How it works')
                                ->content('Price variations below show retail prices. When customers are marked as wholesale, they automatically receive this percentage discount off the retail price. No need for separate wholesale variations!')
                                ->extraAttributes(['class' => 'text-sm text-gray-600']),
                        ]),
                ])
                ->collapsible()
                ->columnSpanFull(),
            
            Forms\Components\Section::make('Price Variations')
                ->description('Select global templates to apply to this product, or create custom variations.')
                ->schema([
                    // Show different UI for create vs edit
                    Forms\Components\Group::make([
                        // For create mode: show template selector
                        Forms\Components\CheckboxList::make('pending_template_ids')
                            ->label('Select Price Variation Templates')
                            ->helperText('Choose templates to apply when the product is created')
                            ->options(function () {
                                return \App\Models\PriceVariation::where('is_global', true)
                                    ->where('is_active', true)
                                    ->with('packagingType')
                                    ->get()
                                    ->mapWithKeys(function ($template) {
                                        $packagingName = $template->packagingType?->display_name ?? 'No packaging';
                                        $price = '$' . number_format($template->price, 2);
                                        $weight = $template->fill_weight ? ' - ' . $template->fill_weight . 'g' : '';
                                        
                                        return [$template->id => "{$template->name} ({$packagingName}){$weight} - {$price}"];
                                    });
                            })
                            ->columns(1)
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\EditProduct)),
                            
                        // For edit mode: show the full price variation management
                        Forms\Components\Group::make([
                            static::getPriceVariationSelectionField(),
                        ])->visible(fn ($livewire) => $livewire instanceof Pages\EditProduct),
                    ])
                ])
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    /**
     * Get the price variation management field with modal template selector
     */
    public static function getPriceVariationSelectionField(): Forms\Components\Component
    {
        return Forms\Components\Group::make([
            // Action button to open template selection modal
            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('select_templates')
                    ->label('Add Price Variations from Templates')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalHeading('Select Price Variation Templates')
                    ->modalDescription('Choose global price variation templates to apply to this product.')
                    ->modalWidth('4xl')
                    ->before(function ($livewire) {
                        // If we're creating a new product, save it first
                        $product = null;
                        if (method_exists($livewire, 'getRecord')) {
                            $product = $livewire->getRecord();
                        } elseif (property_exists($livewire, 'record')) {
                            $product = $livewire->record;
                        }
                        
                        if (!$product || !$product->exists) {
                            // We're in creation mode - save the product now
                            try {
                                // Validate and create the product
                                $livewire->form->validate();
                                $data = $livewire->form->getState();
                                
                                // Create the product
                                $product = $livewire->getModel()::create($data);
                                $livewire->record = $product;
                                
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Product Saved')
                                    ->body('Product created successfully. Now select templates to add.')
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Cannot Save Product')
                                    ->body('Please fill in all required fields before adding templates: ' . $e->getMessage())
                                    ->send();
                                    
                                // Cancel the action
                                return false;
                            }
                        }
                    })
                    ->form([
                        Forms\Components\CheckboxList::make('selected_template_ids')
                            ->label('Available Templates')
                            ->options(function () {
                                return \App\Models\PriceVariation::where('is_global', true)
                                    ->where('is_active', true)
                                    ->with('packagingType')
                                    ->get()
                                    ->mapWithKeys(function ($template) {
                                        $packagingName = $template->packagingType?->display_name ?? 'No packaging';
                                        $price = '$' . number_format($template->price, 2);
                                        $weight = $template->fill_weight ? ' - ' . $template->fill_weight . 'g' : '';
                                        
                                        return [$template->id => "{$template->name} ({$packagingName}){$weight} - {$price}"];
                                    });
                            })
                            ->columns(1)
                            ->gridDirection('row'),
                    ])
                    ->action(function (array $data, $livewire) {
                        // Get the selected template IDs directly from the checkbox list
                        $selectedIds = $data['selected_template_ids'] ?? [];
                        
                        Log::info('Selected template IDs:', $selectedIds);
                        
                        if (empty($selectedIds)) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('No templates selected')
                                ->body('Please select at least one template to add.')
                                ->send();
                            return;
                        }
                        
                        // Get the product record (should exist now due to before() method)
                        $product = null;
                        if (method_exists($livewire, 'getRecord')) {
                            $product = $livewire->getRecord();
                        } elseif (property_exists($livewire, 'record')) {
                            $product = $livewire->record;
                        }
                        
                        if (!$product || !$product->exists) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Product not found. Please try again.')
                                ->send();
                            return;
                        }
                        
                        $createdCount = 0;
                        $skippedCount = 0;
                        
                        // Check if product has any default variation
                        $hasDefault = $product->priceVariations()->where('is_default', true)->exists();
                        
                        // Add new variations from templates
                        foreach ($selectedIds as $templateId) {
                            $template = \App\Models\PriceVariation::find($templateId);
                            if (!$template) {
                                continue;
                            }
                            
                            // Check if variation with same name already exists
                            $existingVariation = $product->priceVariations()
                                ->where('name', $template->name)
                                ->first();
                                
                            if ($existingVariation) {
                                $skippedCount++;
                                continue;
                            }
                            
                            // Create the variation
                            \App\Models\PriceVariation::create([
                                'product_id' => $product->id,
                                'template_id' => $template->id,
                                'name' => $template->name,
                                'packaging_type_id' => $template->packaging_type_id,
                                'sku' => $template->sku,
                                'fill_weight' => $template->fill_weight,
                                'price' => $template->price,
                                'is_default' => !$hasDefault && $createdCount === 0, // First one becomes default if no default exists
                                'is_active' => true,
                                'is_global' => false, // Product-specific variations are not global
                            ]);
                            
                            $createdCount++;
                            $hasDefault = true; // Now we have a default
                        }
                        
                        // Refresh the record to ensure relationship is updated
                        $product->refresh();
                        
                        // Show notification
                        $message = "{$createdCount} template(s) added successfully.";
                        if ($skippedCount > 0) {
                            $message .= " {$skippedCount} template(s) skipped (already exist).";
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Templates Added')
                            ->body($message)
                            ->send();
                            
                        // If we're in create mode and just saved, redirect to edit page
                        if (str_contains(request()->url(), '/create')) {
                            return redirect()->to(static::getUrl('edit', ['record' => $product]));
                        }
                    }),
            ]),
            
            // Display selected price variations as editable table
            Forms\Components\ViewField::make('priceVariations')
                ->view('filament.forms.price-variations-table')
                ->columnSpanFull()
                ->visible(fn ($livewire) => $livewire instanceof Pages\EditProduct),
        ]);
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
                                    Forms\Components\Select::make('master_seed_catalog_id')
                                        ->label('Variety')
                                        ->options(\App\Models\MasterSeedCatalog::where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($catalog) {
                                                $cultivar = !empty($catalog->cultivars) ? $catalog->cultivars[0] : 'No cultivar';
                                                return [$catalog->id => $catalog->common_name . ' (' . $cultivar . ')'];
                                            })
                                        )
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
                                    if (isset($component['master_seed_catalog_id']) && isset($component['percentage'])) {
                                        $mix->masterSeedCatalogs()->attach($component['master_seed_catalog_id'], [
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
                        ->description('Create pricing variations for different packaging types, units, and weights.')
                        ->schema([
                            Forms\Components\ViewField::make('priceVariations')
                                ->view('filament.forms.price-variations-table')
                                ->columnSpanFull(),
                        ]),
                    
                    Forms\Components\Section::make('Quick Setup')
                        ->description('Use these templates to quickly create common packaging variations.')
                        ->schema([
                            Forms\Components\Actions::make([
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
    
    /**
     * Generate variation name in format: "Pricing Type - Packaging (size) - $price"
     * Example: "Retail - Clamshell (24oz) - $5.00"
     */
    protected static function generateVariationName($packagingId, $pricingType, callable $set, callable $get): void
    {
        // Don't auto-generate if name is manually overridden
        if ($get('is_name_manual')) {
            return;
        }
        
        // Don't auto-generate for existing records (when editing)
        if ($get('id')) {
            return;
        }
        
        $parts = [];
        
        // 1. Add pricing type (capitalized)
        if ($pricingType) {
            $pricingTypeNames = [
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
                'bulk' => 'Bulk',
                'special' => 'Special',
                'custom' => 'Custom',
            ];
            $parts[] = $pricingTypeNames[$pricingType] ?? ucfirst($pricingType);
        } else {
            $parts[] = 'Retail'; // Default to retail
        }
        
        // 2. Add packaging information
        if ($packagingId) {
            $packaging = \App\Models\PackagingType::find($packagingId);
            if ($packaging) {
                $packagingPart = $packaging->name;
                
                // Add size information in parentheses
                if ($packaging->capacity_volume && $packaging->volume_unit) {
                    $packagingPart .= ' (' . $packaging->capacity_volume . $packaging->volume_unit . ')';
                }
                
                $parts[] = $packagingPart;
            }
        } else {
            // Handle package-free variations
            $parts[] = 'Package-Free';
        }
        
        // 3. Add price
        $price = $get('price');
        if ($price && is_numeric($price)) {
            $parts[] = '$' . number_format((float)$price, 2);
        }
        
        // Join with " - " separator
        $generatedName = implode(' - ', $parts);
        if ($generatedName) {
            $set('name', $generatedName);
            $set('generated_name', $generatedName); // Store for comparison
        }
    }
} 