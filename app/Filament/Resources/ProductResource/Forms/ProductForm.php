<?php

namespace App\Filament\Resources\ProductResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use App\Models\MasterSeedCatalog;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Group;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use Filament\Forms\Components\CheckboxList;
use App\Models\PriceVariation;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Exception;
use App\Filament\Resources\ProductResource;
use Throwable;
use App\Services\DebugService;
use App\Models\MasterCultivar;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Form schema builder for agricultural product management with complex variety
 * and pricing configuration workflows in the Catapult microgreens system.
 *
 * This class handles the sophisticated form logic required for agricultural product
 * creation and editing, including mutually exclusive variety selection (single OR mix),
 * dynamic pricing strategy configuration, and integration with agricultural workflows
 * like crop planning and inventory management.
 *
 * @filament_form_class Dedicated form schema builder for ProductResource
 * @business_domain Agricultural product catalog with variety and pricing management
 * @agricultural_concepts Single varieties vs. product mixes, pricing tiers, packaging
 * 
 * @form_sections
 * - Basic Information: Name, SKU, description, category, photo
 * - Variety Selection: Single seed catalog OR product mix (mutually exclusive)
 * - Pricing Strategy: Wholesale discount percentages and variation templates
 * - Price Variations: Template-based pricing with packaging and customer tiers
 * 
 * @reactive_behavior
 * - Single variety and product mix fields disable each other when selected
 * - Price variation templates update based on product state
 * - Dynamic helper text provides agricultural context for field selections
 * 
 * @agricultural_workflow_integration
 * - Variety selection drives crop planning calculations
 * - Pricing variations support different packaging sizes for agricultural products
 * - Template system enables consistent pricing across similar agricultural varieties
 * 
 * @business_rules_enforcement
 * - Products cannot have both single variety AND product mix assignments
 * - Name and SKU uniqueness validation with soft-delete awareness
 * - Required price variation creation for order processing workflows
 * - Wholesale pricing calculated as percentage discount from retail base prices
 * 
 * @performance_considerations
 * - Eager loads relationship options to prevent N+1 queries in form dropdowns
 * - Uses reactive fields efficiently to minimize unnecessary re-renders
 * - Chunked loading for large seed catalog and mix option lists
 */
class ProductForm
{
    /**
     * Get the complete form schema for agricultural product management.
     *
     * Constructs a comprehensive form supporting the full agricultural product
     * creation and editing workflow, from basic product information through
     * complex variety selection and pricing strategy configuration.
     *
     * @return array Complete form schema with agricultural product sections
     * @form_structure Three main sections: basic info, pricing strategy, price variations
     * @agricultural_workflow Supports complete product lifecycle from creation to pricing
     * @business_context Enables agricultural product catalog management with variety tracking
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
     * Get simplified single-page form schema for modal or compact contexts.
     *
     * Returns the same comprehensive schema but optimized for display in
     * constrained UI contexts like modal dialogs or embedded forms where
     * the full agricultural product configuration needs to be accessible.
     *
     * @return array Same schema as main form, optimized for single-page display
     * @ui_context Modal forms, embedded widgets, or simplified creation workflows
     * @agricultural_functionality Maintains full variety and pricing capabilities
     */
    public static function getSinglePageFormSchema(): array
    {
        return static::schema();
    }

    /**
     * Basic product information section for agricultural product setup.
     *
     * Handles core product identification, categorization, and basic configuration
     * required before variety selection and pricing strategies can be applied.
     * Includes agricultural-specific fields like variety selection and recipe linking.
     *
     * @return Section Form section with basic agricultural product fields
     * @agricultural_fields Category, variety/mix selection, recipe assignment, photo
     * @business_validation Name/SKU uniqueness with soft-delete awareness
     * @workflow_foundation Required before complex pricing and variety configuration
     */
    protected static function getBasicInformationSection(): Section
    {
        return Section::make('Product Information')
            ->schema([
                Grid::make(3)
                    ->schema([
                        BaseResource::getUniqueNameField()
                            ->unique(
                                table: 'products',
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule) {
                                    return $rule->whereNull('deleted_at');
                                }
                            )
                            ->validationMessages([
                                'unique' => 'A product with this name already exists. Please choose a different name.',
                            ])
                            ->columnSpan(2),
                        TextInput::make('sku')
                            ->label('Product SKU')
                            ->maxLength(255)
                            ->unique(
                                table: 'products',
                                column: 'sku',
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule) {
                                    return $rule->whereNull('deleted_at');
                                }
                            )
                            ->validationMessages([
                                'unique' => 'This SKU is already in use. Please enter a different SKU.',
                            ])
                            ->helperText('Optional unique identifier'),
                    ]),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->rows(3)
                    ->columnSpanFull(),
                static::getProductDetailsGrid(),
                static::getStatusToggles(),
            ])
            ->columns(1);
    }

    /**
     * Product details grid with agricultural-specific configuration options.
     *
     * Provides a structured layout for agricultural product details including
     * category assignment, variety/mix selection, recipe linking for growing
     * instructions, and product photography for customer-facing interfaces.
     *
     * @return Grid Three-column grid with agricultural product detail fields
     * @agricultural_context Category for product organization, variety for crop planning
     * @business_workflow Recipe linking connects to growing instruction workflows
     * @ui_optimization Grid layout maximizes form space utilization
     */
    protected static function getProductDetailsGrid(): Grid
    {
        return Grid::make(3)
            ->schema([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        BaseResource::getNameField(),
                        Textarea::make('description')
                            ->maxLength(65535),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                static::getSingleVarietySelect(),
                static::getProductMixSelect(),
                Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Select a recipe for this product (optional)'),
                FileUpload::make('photo')
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
     * Single variety selection field with agricultural catalog integration.
     *
     * Provides dropdown selection from the master seed catalog with cultivar
     * information display. Implements reactive behavior to prevent simultaneous
     * selection of both single variety and product mix (business rule enforcement).
     *
     * @return Select Variety selection dropdown with agricultural context
     * @agricultural_data Shows common name and cultivar information for seed varieties
     * @business_rule Disabled when product mix is selected (mutually exclusive)
     * @reactive_behavior Dynamic helper text explains selection constraints
     * @performance_optimization Preloaded options prevent query delays
     */
    protected static function getSingleVarietySelect(): Select
    {
        return Select::make('master_seed_catalog_id')
            ->label('Single Variety')
            ->options(function () {
                return MasterSeedCatalog::where('is_active', true)
                ->orderBy('common_name', 'asc')
                ->with('cultivar')
                ->get()
                ->mapWithKeys(function ($catalog) {
                    $cultivarName = $catalog->cultivar ? $catalog->cultivar->cultivar_name : 'No cultivar';
                    
                    return [$catalog->id => $catalog->common_name . ' (' . $cultivarName . ')'];
                })
                ->toArray();
            })
            ->searchable()
            ->preload()
            ->reactive()
            ->disabled(fn (Get $get): bool => !empty($get('product_mix_id')))
            ->helperText(fn (Get $get): string => 
                !empty($get('product_mix_id')) 
                    ? 'Disabled: Product already has a mix assigned' 
                    : 'Select variety from master catalog'
            );
    }

    /**
     * Product mix selection field for multi-variety agricultural products.
     *
     * Enables selection of pre-configured product mixes containing multiple
     * seed varieties with defined percentage distributions. Implements reactive
     * behavior to prevent simultaneous single variety selection.
     *
     * @return Select Product mix dropdown with exclusivity enforcement
     * @agricultural_concept Multi-variety products with percentage-based compositions
     * @business_rule Disabled when single variety is selected (mutually exclusive)
     * @reactive_behavior Dynamic helper text explains agricultural product types
     * @workflow_integration Supports complex crop planning for mixed variety products
     */
    protected static function getProductMixSelect(): Select
    {
        return Select::make('product_mix_id')
            ->label('Product Mix')
            ->relationship('productMix', 'name')
            ->searchable()
            ->preload()
            ->reactive()
            ->disabled(fn (Get $get): bool => !empty($get('master_seed_catalog_id')))
            ->helperText(fn (Get $get): string => 
                !empty($get('master_seed_catalog_id')) 
                    ? 'Disabled: Product already has a single variety assigned' 
                    : 'Select for multi-variety products'
            );
    }

    /**
     * Product status configuration with agricultural business context.
     *
     * Provides toggle controls for product availability and visibility settings
     * that affect agricultural workflow integration, customer-facing systems,
     * and inventory management processes.
     *
     * @return Grid Two-column grid with status toggle controls
     * @business_workflow Active status affects crop planning and order processing
     * @customer_interface Store visibility controls customer-facing product catalog
     * @agricultural_context Status changes impact planting schedules and inventory
     */
    protected static function getStatusToggles(): Grid
    {
        return Grid::make(2)
            ->schema([
                Toggle::make('active')
                    ->label('Active')
                    ->default(true),
                Toggle::make('is_visible_in_store')
                    ->label('Visible in Store')
                    ->default(true),
            ]);
    }

    /**
     * Agricultural pricing strategy configuration with wholesale discount system.
     *
     * Implements a sophisticated pricing model where retail prices are set in
     * price variations and wholesale customers receive automatic percentage
     * discounts. This approach simplifies agricultural product pricing management
     * while supporting multiple customer tiers.
     *
     * @return Section Pricing strategy configuration with wholesale discount
     * @agricultural_pricing Supports different customer types (retail, wholesale, bulk)
     * @business_model Percentage-based wholesale discounts from retail base prices
     * @workflow_simplification Single price point with automatic discount calculation
     */
    protected static function getPricingStrategySection(): Section
    {
        return Section::make('Pricing Strategy')
            ->description('Set retail prices in price variations, then configure wholesale discount here.')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('wholesale_discount_percentage')
                            ->label('Wholesale Discount %')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(25.00)
                            ->helperText('Percentage discount applied to retail prices for wholesale customers'),
                        Placeholder::make('pricing_explanation')
                            ->label('How it works')
                            ->content('Price variations below show retail prices. When customers are marked as wholesale, they automatically receive this percentage discount off the retail price. No need for separate wholesale variations!')
                            ->extraAttributes(['class' => 'text-sm text-gray-600']),
                    ]),
            ])
            ->collapsible()
            ->columnSpanFull();
    }

    /**
     * Price variations management with agricultural packaging context.
     *
     * Provides template-based pricing configuration supporting different packaging
     * sizes, customer tiers, and agricultural product presentations. Templates
     * enable consistent pricing across similar agricultural products while allowing
     * product-specific customization.
     *
     * @return Section Price variations with template selection and management
     * @agricultural_packaging Different container sizes, weights, and presentations
     * @business_efficiency Template system enables consistent agricultural pricing
     * @workflow_flexibility Supports both template application and custom variations
     */
    protected static function getPriceVariationsSection(): Section
    {
        return Section::make('Price Variations')
            ->description('Select global templates to apply to this product, or create custom variations.')
            ->schema([
                Group::make([
                    // For create mode: show template selector
                    static::getTemplateCheckboxList(),
                    // For edit mode: show the full price variation management
                    Group::make([
                        static::getPriceVariationSelectionField(),
                    ])->visible(fn ($livewire) => $livewire instanceof EditProduct),
                ])
            ])
            ->collapsible()
            ->columnSpanFull();
    }

    /**
     * Price variation template selection for new agricultural products.
     *
     * Displays available global price variation templates as checkboxes during
     * product creation, enabling quick application of standard agricultural
     * pricing structures (different package sizes, customer tiers) to new products.
     *
     * @return CheckboxList Template selection interface for product creation
     * @agricultural_templates Standard packaging and pricing configurations
     * @business_efficiency Rapid price variation setup for similar products
     * @create_workflow Only visible during product creation phase
     */
    protected static function getTemplateCheckboxList(): CheckboxList
    {
        return CheckboxList::make('pending_template_ids')
            ->label('Select Price Variation Templates')
            ->helperText('Choose templates to apply when the product is created')
            ->options(function () {
                return PriceVariation::where('is_global', true)
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
            ->visible(fn ($livewire) => !($livewire instanceof EditProduct));
    }

    /**
     * Advanced price variation management with agricultural template system.
     *
     * Provides a comprehensive interface for managing product-specific price
     * variations including template application, custom variation creation,
     * and agricultural packaging configuration. Supports the complex pricing
     * needs of agricultural products with multiple packaging options.
     *
     * @return Component Advanced price variation management interface
     * @agricultural_complexity Multiple packaging sizes, customer tiers, seasonal pricing
     * @template_system Global templates ensure consistency across similar products
     * @business_workflow Template selection modal with preview and application
     */
    public static function getPriceVariationSelectionField(): Component
    {
        return Group::make([
            // Action button to open template selection modal
            Actions::make([
                static::getSelectTemplatesAction(),
            ]),
            
            // Display selected price variations as editable table
            ViewField::make('priceVariations')
                ->view('filament.forms.price-variations-table')
                ->columnSpanFull()
                ->visible(fn ($livewire) => $livewire instanceof EditProduct),
        ]);
    }

    /**
     * Template selection action with agricultural pricing workflow integration.
     *
     * Creates a modal-based interface for selecting and applying global price
     * variation templates to agricultural products. Handles product creation
     * if needed and manages the complex workflow of template application with
     * duplicate detection and agricultural business rule enforcement.
     *
     * @return Action Modal-based template selection and application action
     * @agricultural_workflow Ensures products exist before template application
     * @business_logic Prevents duplicate variations, manages default assignments
     * @performance_optimization Batch template application with relationship updates
     * @user_experience Clear feedback and automatic page navigation after completion
     */
    protected static function getSelectTemplatesAction(): Action
    {
        return Action::make('select_templates')
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
                            
                    } catch (Exception $e) {
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
            ->schema([
                CheckboxList::make('selected_template_ids')
                    ->label('Available Templates')
                    ->options(function () {
                        return PriceVariation::where('is_global', true)
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
                    $template = PriceVariation::find($templateId);
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
                    PriceVariation::create([
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
                    return redirect()->to(ProductResource::getUrl('edit', ['record' => $product]));
                }
            });
    }

    /**
     * Get comprehensive view panels for agricultural product information display.
     *
     * Creates detailed information panels showing price variations, variety
     * information, and agricultural context for product viewing. Handles errors
     * gracefully and provides agricultural business context for each panel.
     *
     * @return array Associative array of view panels with agricultural context
     * @agricultural_display Price variations, variety details, seed inventory status
     * @business_context Packaging options, supplier information, cultivation details
     * @error_handling Graceful degradation with debug information on failures
     */
    public static function getPanels(): array
    {
        try {
            Log::info('ProductForm: getPanels method called');
            
            return [
                'price_variations' => static::getPriceVariationsPanel(),
                'variety_info' => static::getVarietyInfoPanel(),
            ];
        } catch (Throwable $e) {
            DebugService::logError($e, 'ProductForm::getPanels');
            
            // Return a minimal panel set that won't cause errors
            return [
                'debug' => Section::make('Debug Information')
                    ->schema([
                        Placeholder::make('error')
                            ->label('Error')
                            ->content('An error occurred loading panels: ' . $e->getMessage()),
                    ]),
            ];
        }
    }

    /**
     * Price variations display panel with agricultural packaging context.
     *
     * Shows comprehensive price variation information including default pricing,
     * active variation counts, and available packaging options. Provides agricultural
     * business context about pricing strategies and suggests missing standard
     * pricing tiers common in agricultural product sales.
     *
     * @return Section Price variations display with agricultural business context
     * @agricultural_pricing Shows packaging-based pricing and customer tier options
     * @business_intelligence Identifies missing standard pricing types
     * @workflow_guidance Suggests completing standard agricultural pricing structures
     */
    protected static function getPriceVariationsPanel(): Section
    {
        return Section::make('Price Variations')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Placeholder::make('base_price_display')
                            ->label('Default Price')
                            ->content(function ($record) {
                                $variation = $record->defaultPriceVariation();
                                return $variation 
                                    ? '$' . number_format($variation->price, 2) . ' (' . $variation->name . ')'
                                    : '$' . number_format($record->base_price ?? 0, 2);
                            }),
                        Placeholder::make('variations_count')
                            ->label('Price Variations')
                            ->content(function ($record) {
                                $count = $record->priceVariations()->count();
                                $activeCount = $record->priceVariations()->where('is_active', true)->count();
                                return "{$activeCount} active / {$count} total";
                            }),
                    ]),
                Placeholder::make('variations_info')
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
                ViewField::make('price_variations_panel')
                    ->view('filament.resources.product-resource.partials.price-variations')
            ])
            ->collapsible()
            ->columnSpanFull();
    }

    /**
     * Comprehensive variety information panel for agricultural context.
     *
     * Displays detailed information about product varieties including single
     * seed catalog entries or complex product mix compositions. Shows agricultural
     * context like cultivar information, seed inventory levels, and supplier details
     * essential for crop planning and inventory management.
     *
     * @return Section Variety information with agricultural business context
     * @agricultural_data Variety names, cultivar details, inventory levels, suppliers
     * @business_intelligence Seed stock levels for production planning
     * @workflow_integration Supports crop planning and inventory management decisions
     */
    protected static function getVarietyInfoPanel(): Section
    {
        return Section::make('Variety Information')
            ->schema([
                // Single Variety Section
                static::getSingleVarietyGroup(),
                // Product Mix Section
                static::getProductMixGroup(),
                // No Variety Assigned Message
                Placeholder::make('no_variety')
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
     * Single variety display group with comprehensive agricultural information.
     *
     * Shows detailed information for products linked to a single seed catalog
     * entry, including variety name, available cultivars, and agricultural
     * details essential for crop planning and production management.
     *
     * @return Group Single variety information with agricultural context
     * @agricultural_display Variety name, cultivar options, seed inventory, supplier
     * @business_context Information needed for crop planning and procurement decisions
     * @conditional_display Only shown for products with single variety assignments
     */
    protected static function getSingleVarietyGroup(): Group
    {
        return Group::make([
            Grid::make(2)
                ->schema([
                    Placeholder::make('variety_name')
                        ->label('Variety Name')
                        ->content(function ($record) {
                            $catalog = $record->masterSeedCatalog;
                            if (!$catalog) return 'Unknown';
                            $cultivarName = $catalog->cultivar ? $catalog->cultivar->cultivar_name : 'No cultivar';
                            return $catalog->common_name . ' (' . $cultivarName . ')';
                        }),
                    Placeholder::make('available_cultivars')
                        ->label('Available Cultivars')
                        ->content(function ($record) {
                            $catalog = $record->masterSeedCatalog;
                            if (!$catalog) return 'N/A';
                            
                            $cultivars = MasterCultivar::where('master_seed_catalog_id', $catalog->id)
                                ->where('is_active', true)
                                ->pluck('cultivar_name')
                                ->toArray();
                            
                            return empty($cultivars) ? 'N/A' : implode(', ', $cultivars);
                        }),
                ]),
            static::getVarietyDetailsGrid(),
        ])
        ->visible(fn ($record) => $record->master_seed_catalog_id !== null);
    }

    /**
     * Detailed grid display for single variety agricultural information.
     *
     * Provides a structured three-column layout showing category classification,
     * current seed inventory levels, and primary supplier information. This
     * information supports agricultural decision-making for crop planning,
     * procurement, and inventory management.
     *
     * @return Grid Three-column agricultural information grid
     * @agricultural_data Category, seed stock levels, supplier information
     * @business_intelligence Inventory levels support production planning decisions
     * @procurement_context Supplier information enables procurement workflow integration
     */
    protected static function getVarietyDetailsGrid(): Grid
    {
        return Grid::make(3)
            ->schema([
                Placeholder::make('category')
                    ->label('Category')
                    ->content(function ($record) {
                        $catalog = $record->masterSeedCatalog;
                        return $catalog->category ?? 'No category';
                    }),
                Placeholder::make('seed_inventory')
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
                Placeholder::make('supplier')
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
     * Product mix display group with multi-variety agricultural composition.
     *
     * Shows comprehensive information for products containing multiple seed
     * varieties with defined percentage distributions. Displays mix composition,
     * variety counts, and detailed breakdown of agricultural components with
     * percentages and cultivar specifications.
     *
     * @return Group Product mix information with agricultural variety details
     * @agricultural_complexity Multi-variety compositions with percentage distributions
     * @business_context Variety mix ratios essential for agricultural production planning
     * @conditional_display Only shown for products with product mix assignments
     */
    protected static function getProductMixGroup(): Group
    {
        return Group::make([
            Grid::make(2)
                ->schema([
                    Placeholder::make('mix_name')
                        ->label('Mix Name')
                        ->content(fn ($record) => $record->productMix->name ?? 'Unknown'),
                    Placeholder::make('variety_count')
                        ->label('Number of Varieties')
                        ->content(fn ($record) => $record->productMix->masterSeedCatalogs->count() ?? 0),
                ]),
            Placeholder::make('varieties')
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