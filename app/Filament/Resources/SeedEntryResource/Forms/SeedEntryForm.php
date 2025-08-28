<?php

namespace App\Filament\Resources\SeedEntryResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use App\Models\SeedEntry;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use App\Filament\Resources\BaseResource;
use Filament\Forms;

/**
 * Seed entry form schema for agricultural seed catalog management.
 * Provides comprehensive form configuration for managing seed varieties, supplier information,
 * pricing variations, and agricultural metadata supporting microgreens production operations.
 *
 * @business_domain Agricultural seed catalog and supplier management
 * @form_architecture Extracted from SeedEntryResource following Filament guidelines
 * @seed_identification Common name and cultivar selection with dynamic filtering
 * @supplier_integration Supplier management with inline creation and SKU tracking
 * @pricing_variations Multiple size/weight options with agricultural pricing context
 * @metadata_management Tags, descriptions, and images for comprehensive seed information
 */
class SeedEntryForm
{
    /**
     * Returns comprehensive seed entry form schema for agricultural seed catalog management.
     * Provides structured sections for seed identification, supplier information, metadata,
     * and pricing variations to support complete microgreens production seed management.
     *
     * @seed_identification Common name and cultivar selection with agricultural context
     * @supplier_management Comprehensive supplier information and product linking
     * @pricing_structure Multiple variations for different package sizes and weights
     * @agricultural_metadata Tags, descriptions, and images for production planning
     * @return array Complete form schema array for seed entry management
     */
    public static function schema(): array
    {
        return [
            Section::make('Seed Identification')
                ->description('Identify the seed type and variety. Both common name and cultivar are required.')
                ->icon('heroicon-o-identification')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            static::getCommonNameField(),
                            static::getCultivarNameField(),
                        ]),
                ]),

            Section::make('Supplier Information')
                ->description('Specify the supplier and their product details.')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    static::getSupplierField(),
                    Grid::make(2)
                        ->schema([
                            static::getSupplierSkuField(),
                            static::getUrlField(),
                        ]),
                ]),

            Section::make('Additional Details')
                ->description('Optional information to enhance the seed entry.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    static::getImageUrlField(),
                    static::getDescriptionField(),
                    static::getTagsField(),
                ]),

            static::getSeedVariationsSection(),
        ];
    }

    protected static function getCommonNameField(): Select
    {
        return Select::make('common_name')
            ->label('Common Name')
            ->options(function () {
                return SeedEntry::whereNotNull('common_name')
                    ->where('common_name', '<>', '')
                    ->distinct()
                    ->orderBy('common_name')
                    ->pluck('common_name', 'common_name')
                    ->toArray();
            })
            ->searchable()
            ->allowHtml()
            ->createOptionForm([
                BaseResource::getNameField('New Common Name')
                    ->statePath('common_name'),
            ])
            ->createOptionUsing(function (array $data): string {
                return $data['common_name'];
            })
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, Set $set) {
                // When common name changes, filter cultivar options
                $set('cultivar_name', null); // Reset cultivar selection
            });
    }

    protected static function getCultivarNameField(): Select
    {
        return Select::make('cultivar_name')
            ->required()
            ->label('Cultivar Name')
            ->options(function (Get $get) {
                $commonName = $get('common_name');

                $query = SeedEntry::whereNotNull('cultivar_name')
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
                BaseResource::getNameField('New Cultivar Name')
                    ->statePath('cultivar_name'),
            ])
            ->createOptionUsing(function (array $data): string {
                return $data['cultivar_name'];
            })
            ->placeholder(function (Get $get) {
                $commonName = $get('common_name');

                return $commonName ? "Select or create cultivar for {$commonName}" : 'Select common name first';
            })
            ->disabled(fn (Get $get): bool => empty($get('common_name')))
            ->helperText('Cultivar options will filter based on your common name selection');
    }

    protected static function getSupplierField(): Select
    {
        return Select::make('supplier_id')
            ->label('Supplier')
            ->relationship('supplier', 'name')
            ->required()
            ->searchable()
            ->preload()
            ->createOptionForm([
                BaseResource::getNameField(),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
                Textarea::make('notes')
                    ->maxLength(65535),
            ]);
    }

    protected static function getSupplierSkuField(): TextInput
    {
        return TextInput::make('supplier_sku')
            ->maxLength(255)
            ->label('Supplier SKU')
            ->placeholder('e.g., BSL-001, BASIL-25G')
            ->helperText('Supplier\'s product code or identifier');
    }

    protected static function getUrlField(): TextInput
    {
        return TextInput::make('url')
            ->url()
            ->maxLength(255)
            ->label('Product URL')
            ->placeholder('https://supplier.com/product-page')
            ->helperText('Link to supplier\'s product page');
    }

    protected static function getImageUrlField(): TextInput
    {
        return TextInput::make('image_url')
            ->url()
            ->maxLength(255)
            ->label('Image URL')
            ->placeholder('https://example.com/seed-image.jpg')
            ->helperText('URL to product image');
    }

    protected static function getDescriptionField(): Textarea
    {
        return Textarea::make('description')
            ->maxLength(65535)
            ->rows(3)
            ->placeholder('Optional description of this seed variety...')
            ->columnSpanFull();
    }

    protected static function getTagsField(): TagsInput
    {
        return TagsInput::make('tags')
            ->placeholder('organic, heirloom, fast-growing')
            ->helperText('Add tags to categorize this seed')
            ->columnSpanFull();
    }

    /**
     * Seed variations section for agricultural pricing and package management.
     * Provides comprehensive variation management for different seed package sizes,
     * weights, and pricing options to support diverse microgreens production needs.
     *
     * @pricing_variations Multiple package sizes with agricultural pricing context
     * @create_edit_modes Different UI presentation for creation vs editing workflows
     * @package_management Size, weight, and availability options for production planning
     * @agricultural_context Variations tailored for microgreens production requirements
     * @return Section Seed variations and pricing configuration section
     */
    protected static function getSeedVariationsSection(): Section
    {
        return Section::make('Seed Variations & Pricing')
            ->description('Manage different sizes, weights, and pricing options for this seed entry.')
            ->schema([
                // Show different UI for create vs edit mode (following ProductResource pattern)
                Group::make([
                    // For create mode: show simple info message
                    Placeholder::make('create_mode_info')
                        ->label('Price Variations')
                        ->content('Save the seed entry first, then you can add price variations for different sizes and weights.')
                        ->extraAttributes(['class' => 'text-sm text-gray-600'])
                        ->visible(function ($livewire) {
                            // Check if we have a record - if not, we're in create mode
                            $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

                            return ! $record || ! $record->exists;
                        }),

                    // For edit mode: show the full variations management
                    Group::make([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('variations_count')
                                    ->label('Seed Variations')
                                    ->content(function ($record) {
                                        if (! $record) {
                                            return '0 variations';
                                        }
                                        $count = $record->variations()->count();
                                        $activeCount = $record->variations()->where('is_available', true)->count();

                                        return "{$activeCount} available / {$count} total";
                                    }),
                                Placeholder::make('default_variation_display')
                                    ->label('Primary Variation')
                                    ->content(function ($record) {
                                        if (! $record) {
                                            return 'No variations yet';
                                        }
                                        $defaultVariation = $record->variations()->first();

                                        return $defaultVariation
                                            ? $defaultVariation->size.' - $'.number_format($defaultVariation->current_price, 2)
                                            : 'No variations created';
                                    }),
                            ]),
                        Placeholder::make('variations_info')
                            ->content(function ($record) {
                                $content = 'Seed variations allow you to offer different package sizes, weights, and prices for the same seed type.';

                                if ($record) {
                                    $variationTypes = $record->variations()->pluck('size')->toArray();
                                    if (! empty($variationTypes)) {
                                        $content .= "<br><br>Current variations: <span class='text-primary-500'>".implode(', ', $variationTypes).'</span>';
                                    }
                                }

                                return $content;
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'prose']),
                        ViewField::make('seed_variations_panel')
                            ->view('filament.resources.seed-entry-resource.partials.seed-variations')
                            ->columnSpanFull(),
                    ])->visible(function ($livewire) {
                        // Check if we have a record - if so, we're in edit mode
                        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

                        return $record && $record->exists;
                    }),
                ]),
            ])
            ->collapsible();
    }
}
