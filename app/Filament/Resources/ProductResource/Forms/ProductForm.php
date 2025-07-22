<?php

namespace App\Filament\Resources\ProductResource\Forms;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProductForm
{
    /**
     * Get the complete form schema for ProductResource
     */
    public static function schema(): array
    {
        return [
            static::getBasicInformationSection(),
            static::getPricingStrategySection(),
            static::getPriceVariationsSection(),
        ];
    }

    /**
     * Get the single-page form schema (used in some contexts)
     */
    public static function getSinglePageFormSchema(): array
    {
        return static::schema();
    }

    /**
     * Basic product information section
     */
    protected static function getBasicInformationSection(): Section
    {
        return Forms\Components\Section::make('Product Information')
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
                static::getProductDetailsGrid(),
                static::getStatusToggles(),
            ])
            ->columns(1);
    }

    /**
     * Product details grid (category, variety, recipe, photo)
     */
    protected static function getProductDetailsGrid(): Grid
    {
        return Forms\Components\Grid::make(3)
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
                static::getSingleVarietySelect(),
                static::getProductMixSelect(),
                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Select a recipe for this product (optional)'),
                Forms\Components\FileUpload::make('photo')
                    ->label('Primary Photo')
                    ->image()
                    ->directory('product-photos')
                    ->maxSize(5120)
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('800')
                    ->disk('public'),
            ]);
    }

    /**
     * Single variety select field with reactive behavior
     */
    protected static function getSingleVarietySelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('master_seed_catalog_id')
            ->label('Single Variety')
            ->options(function () {
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
            );
    }

    /**
     * Product mix select field with reactive behavior
     */
    protected static function getProductMixSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('product_mix_id')
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
            );
    }

    /**
     * Status toggle switches
     */
    protected static function getStatusToggles(): Grid
    {
        return Forms\Components\Grid::make(2)
            ->schema([
                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Toggle::make('is_visible_in_store')
                    ->label('Visible in Store')
                    ->default(true),
            ]);
    }

    /**
     * Pricing strategy section
     */
    protected static function getPricingStrategySection(): Section
    {
        return Forms\Components\Section::make('Pricing Strategy')
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
            ->columnSpanFull();
    }

    /**
     * Price variations section
     */
    protected static function getPriceVariationsSection(): Section
    {
        return Forms\Components\Section::make('Price Variations')
            ->description('Select global templates to apply to this product, or create custom variations.')
            ->schema([
                Forms\Components\Group::make([
                    // For create mode: show template selector
                    static::getTemplateCheckboxList(),
                    // For edit mode: show the full price variation management
                    Forms\Components\Group::make([
                        static::getPriceVariationSelectionField(),
                    ])->visible(fn ($livewire) => $livewire instanceof Pages\EditProduct),
                ])
            ])
            ->collapsible()
            ->columnSpanFull();
    }

    /**
     * Template checkbox list for create mode
     */
    protected static function getTemplateCheckboxList(): Forms\Components\CheckboxList
    {
        return Forms\Components\CheckboxList::make('pending_template_ids')
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
            ->visible(fn ($livewire) => !($livewire instanceof Pages\EditProduct));
    }

    /**
     * Price variation management field with modal template selector
     */
    public static function getPriceVariationSelectionField(): Component
    {
        return Forms\Components\Group::make([
            // Action button to open template selection modal
            Forms\Components\Actions::make([
                static::getSelectTemplatesAction(),
            ]),
            
            // Display selected price variations as editable table
            Forms\Components\ViewField::make('priceVariations')
                ->view('filament.forms.price-variations-table')
                ->columnSpanFull()
                ->visible(fn ($livewire) => $livewire instanceof Pages\EditProduct),
        ]);
    }

    /**
     * Get the select templates action for price variations
     */
    protected static function getSelectTemplatesAction(): Actions\Action
    {
        return Forms\Components\Actions\Action::make('select_templates')
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
                        
                        Notification::make()
                            ->success()
                            ->title('Product Saved')
                            ->body('Product created successfully. Now select templates to add.')
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
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
                    Notification::make()
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
                    Notification::make()
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
                
                Notification::make()
                    ->success()
                    ->title('Templates Added')
                    ->body($message)
                    ->send();
                    
                // If we're in create mode and just saved, redirect to edit page
                if (str_contains(request()->url(), '/create')) {
                    return redirect()->to(\App\Filament\Resources\ProductResource::getUrl('edit', ['record' => $product]));
                }
            });
    }

    /**
     * Get panels for view page
     */
    public static function getPanels(): array
    {
        try {
            Log::info('ProductForm: getPanels method called');
            
            return [
                'price_variations' => static::getPriceVariationsPanel(),
                'variety_info' => static::getVarietyInfoPanel(),
            ];
        } catch (\Throwable $e) {
            \App\Services\DebugService::logError($e, 'ProductForm::getPanels');
            
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

    /**
     * Price variations panel for view page
     */
    protected static function getPriceVariationsPanel(): Section
    {
        return Forms\Components\Section::make('Price Variations')
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
            ->columnSpanFull();
    }

    /**
     * Variety information panel for view page
     */
    protected static function getVarietyInfoPanel(): Section
    {
        return Forms\Components\Section::make('Variety Information')
            ->schema([
                // Single Variety Section
                static::getSingleVarietyGroup(),
                // Product Mix Section
                static::getProductMixGroup(),
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
            ->columnSpanFull();
    }

    /**
     * Single variety group for view panel
     */
    protected static function getSingleVarietyGroup(): Group
    {
        return Forms\Components\Group::make([
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
            static::getVarietyDetailsGrid(),
        ])
        ->visible(fn ($record) => $record->master_seed_catalog_id !== null);
    }

    /**
     * Variety details grid for single variety view
     */
    protected static function getVarietyDetailsGrid(): Grid
    {
        return Forms\Components\Grid::make(3)
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
            ]);
    }

    /**
     * Product mix group for view panel
     */
    protected static function getProductMixGroup(): Group
    {
        return Forms\Components\Group::make([
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
        ->visible(fn ($record) => $record->product_mix_id !== null);
    }
}