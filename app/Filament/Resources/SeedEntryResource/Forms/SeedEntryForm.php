<?php

namespace App\Filament\Resources\SeedEntryResource\Forms;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

class SeedEntryForm
{
    /**
     * Returns Filament form schema for SeedEntry
     */
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Seed Identification')
                ->description('Identify the seed type and variety. Both common name and cultivar are required.')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            static::getCommonNameField(),
                            static::getCultivarNameField(),
                        ]),
                ]),

            Forms\Components\Section::make('Supplier Information')
                ->description('Specify the supplier and their product details.')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    static::getSupplierField(),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            static::getSupplierSkuField(),
                            static::getUrlField(),
                        ]),
                ]),

            Forms\Components\Section::make('Additional Details')
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

    protected static function getCommonNameField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('common_name')
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
            ->live(onBlur: true)()
            ->afterStateUpdated(function ($state, Set $set) {
                // When common name changes, filter cultivar options
                $set('cultivar_name', null); // Reset cultivar selection
            });
    }

    protected static function getCultivarNameField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('cultivar_name')
            ->required()
            ->label('Cultivar Name')
            ->options(function (Get $get) {
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
            ->placeholder(function (Get $get) {
                $commonName = $get('common_name');

                return $commonName ? "Select or create cultivar for {$commonName}" : 'Select common name first';
            })
            ->disabled(fn (Get $get): bool => empty($get('common_name')))
            ->helperText('Cultivar options will filter based on your common name selection');
    }

    protected static function getSupplierField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('supplier_id')
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
            ]);
    }

    protected static function getSupplierSkuField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('supplier_sku')
            ->maxLength(255)
            ->label('Supplier SKU')
            ->placeholder('e.g., BSL-001, BASIL-25G')
            ->helperText('Supplier\'s product code or identifier');
    }

    protected static function getUrlField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('url')
            ->url()
            ->maxLength(255)
            ->label('Product URL')
            ->placeholder('https://supplier.com/product-page')
            ->helperText('Link to supplier\'s product page');
    }

    protected static function getImageUrlField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('image_url')
            ->url()
            ->maxLength(255)
            ->label('Image URL')
            ->placeholder('https://example.com/seed-image.jpg')
            ->helperText('URL to product image');
    }

    protected static function getDescriptionField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('description')
            ->maxLength(65535)
            ->rows(3)
            ->placeholder('Optional description of this seed variety...')
            ->columnSpanFull();
    }

    protected static function getTagsField(): Forms\Components\TagsInput
    {
        return Forms\Components\TagsInput::make('tags')
            ->placeholder('organic, heirloom, fast-growing')
            ->helperText('Add tags to categorize this seed')
            ->columnSpanFull();
    }

    protected static function getSeedVariationsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Seed Variations & Pricing')
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

                            return ! $record || ! $record->exists;
                        }),

                    // For edit mode: show the full variations management
                    Forms\Components\Group::make([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('variations_count')
                                    ->label('Seed Variations')
                                    ->content(function ($record) {
                                        if (! $record) {
                                            return '0 variations';
                                        }
                                        $count = $record->variations()->count();
                                        $activeCount = $record->variations()->where('is_available', true)->count();

                                        return "{$activeCount} available / {$count} total";
                                    }),
                                Forms\Components\Placeholder::make('default_variation_display')
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
                        Forms\Components\Placeholder::make('variations_info')
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
                        Forms\Components\ViewField::make('seed_variations_panel')
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
