<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceVariationResource\Pages;
use App\Filament\Resources\PriceVariationResource\RelationManagers;
use App\Models\PriceVariation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PriceVariationResource extends Resource
{
    protected static ?string $model = PriceVariation::class;

    // Hide from navigation since price variations are managed within ProductResource
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Compact header with template toggle
                Forms\Components\Group::make([
                    Forms\Components\Toggle::make('is_global')
                        ->label('Global Pricing Template')
                        ->helperText('Create a reusable template for any product')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $set('is_default', false);
                                $set('product_id', null);
                            }
                        }),
                ])
                ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 p-4 rounded-lg mb-6']),

                // Primary Information Section
                Forms\Components\Section::make('Basic Information')
                    ->description(fn (Forms\Get $get): string => 
                        $get('is_global') 
                            ? 'This template can be applied to any product' 
                            : 'Define pricing for a specific product'
                    )
                    ->schema([
                        // Product selection for non-global variations
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->label('Product')
                            ->required(fn (Forms\Get $get): bool => !$get('is_global'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a product...')
                            ->visible(fn (Forms\Get $get): bool => !$get('is_global'))
                            ->columnSpanFull(),

                        // Pricing type and unit selector
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('pricing_type')
                                    ->label('Pricing Type')
                                    ->options([
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale', 
                                        'bulk' => 'Bulk',
                                        'special' => 'Special',
                                        'custom' => 'Custom',
                                    ])
                                    ->default('retail')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-generate name if not manually overridden
                                        if (!$get('manual_name_override')) {
                                            self::generateVariationName($get('packaging_type_id'), $state, $set, $get);
                                        }
                                        // Show pricing unit for bulk
                                        if ($state === 'bulk') {
                                            $set('show_pricing_unit', true);
                                        }
                                    }),
                                    
                                Forms\Components\Select::make('pricing_unit')
                                    ->label('Pricing Unit')
                                    ->options([
                                        'per_item' => 'Per Item/Package',
                                        'per_g' => 'Per Gram',
                                        'per_kg' => 'Per Kilogram',
                                        'per_lb' => 'Per Pound',
                                        'per_oz' => 'Per Ounce',
                                    ])
                                    ->default('per_item')
                                    ->reactive()
                                    ->visible(fn (Forms\Get $get): bool => 
                                        $get('pricing_type') === 'bulk' || 
                                        $get('pricing_type') === 'wholesale' ||
                                        !$get('packaging_type_id')
                                    )
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-generate name if not manually overridden
                                        if (!$get('manual_name_override')) {
                                            self::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                                        }
                                    }),
                            ]),
                            
                        // Hidden field to track manual override
                        Forms\Components\Hidden::make('manual_name_override')
                            ->default(false),

                        // Core fields in logical order
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Variation Name')
                                    ->placeholder('Auto-generated or enter custom name')
                                    ->required()
                                    ->maxLength(255)
                                    ->autofocus()
                                    ->reactive()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('regenerate_name')
                                            ->icon('heroicon-m-arrow-path')
                                            ->label('Regenerate')
                                            ->action(function (callable $set, callable $get) {
                                                $set('manual_name_override', false);
                                                self::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                                            })
                                            ->visible(fn (callable $get): bool => (bool) $get('manual_name_override'))
                                    )
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Allow manual override - if user types, don't auto-generate
                                        if ($state && strlen($state) > 1) {
                                            $set('manual_name_override', true);
                                        }
                                    }),

                                Forms\Components\TextInput::make('price')
                                    ->label(function (Forms\Get $get): string {
                                        $unit = $get('pricing_unit');
                                        return match($unit) {
                                            'per_g' => 'Price per Gram',
                                            'per_kg' => 'Price per Kilogram',
                                            'per_lb' => 'Price per Pound',
                                            'per_oz' => 'Price per Ounce',
                                            default => 'Price',
                                        };
                                    })
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->required()
                                    ->inputMode('decimal')
                                    ->helperText(function (Forms\Get $get): ?string {
                                        $unit = $get('pricing_unit');
                                        if ($unit && $unit !== 'per_item') {
                                            $fillWeight = $get('fill_weight');
                                            if ($fillWeight && is_numeric($fillWeight)) {
                                                $price = $get('price');
                                                if ($price && is_numeric($price)) {
                                                    // Calculate total price based on unit
                                                    $total = match($unit) {
                                                        'per_g' => $price * $fillWeight,
                                                        'per_kg' => $price * ($fillWeight / 1000),
                                                        'per_lb' => $price * ($fillWeight / 453.592),
                                                        'per_oz' => $price * ($fillWeight / 28.35),
                                                        default => 0,
                                                    };
                                                    return 'Total price: $' . number_format($total, 2);
                                                }
                                            }
                                        }
                                        return null;
                                    })
                                    ->reactive(),

                                Forms\Components\Select::make('packaging_type_id')
                                    ->relationship('packagingType', 'name', function ($query) {
                                        return $query->where('is_active', true);
                                    })
                                    ->getOptionLabelFromRecordUsing(fn (\App\Models\PackagingType $record): string => $record->display_name)
                                    ->label('Packaging')
                                    ->placeholder('Select packaging or leave empty')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Only auto-generate name for non-global variations and if not manually overridden
                                        if (!$get('manual_name_override') && !$get('is_global')) {
                                            self::generateVariationName($state, $get('pricing_type'), $set, $get);
                                        }
                                    })
                                    ->hint('Optional')
                                    ->helperText('Choose the packaging type for this price variation')
                                    ->dehydrated(true)
                                    ->visible(true),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),

                // Product Details Section
                Forms\Components\Section::make('Product Details')
                    ->description('Specify quantity, weight, or packaging details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('fill_weight')
                                    ->label(function (Forms\Get $get): string {
                                        $packagingId = $get('packaging_type_id');
                                        $name = strtolower($get('name') ?? '');
                                        
                                        if (!$packagingId) {
                                            // Infer from name if no packaging
                                            if (str_contains($name, 'tray') || str_contains($name, 'live')) {
                                                return 'Quantity (trays)';
                                            }
                                            if (str_contains($name, 'bulk') || str_contains($name, 'pound')) {
                                                return 'Weight (grams)';
                                            }
                                            if (str_contains($name, 'each') || str_contains($name, 'unit')) {
                                                return 'Units';
                                            }
                                            return 'Quantity / Weight';
                                        }
                                        
                                        $packaging = \App\Models\PackagingType::find($packagingId);
                                        if ($packaging && str_contains(strtolower($packaging->name), 'live')) {
                                            return 'Quantity (trays)';
                                        }
                                        if ($packaging && str_contains(strtolower($packaging->name), 'bulk')) {
                                            return 'Weight (grams)';
                                        }
                                        
                                        return 'Fill Weight (grams)';
                                    })
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder(function (Forms\Get $get): string {
                                        $packagingId = $get('packaging_type_id');
                                        if (!$packagingId) {
                                            return 'Enter amount';
                                        }
                                        return 'Enter weight or quantity';
                                    })
                                    ->suffix(function (Forms\Get $get): string {
                                        $packagingId = $get('packaging_type_id');
                                        $name = strtolower($get('name') ?? '');
                                        
                                        if (!$packagingId) {
                                            if (str_contains($name, 'tray') || str_contains($name, 'live')) {
                                                return 'trays';
                                            }
                                            if (str_contains($name, 'bulk') || str_contains($name, 'pound')) {
                                                return 'g';
                                            }
                                            return 'units';
                                        }
                                        
                                        $packaging = \App\Models\PackagingType::find($packagingId);
                                        if ($packaging && str_contains(strtolower($packaging->name), 'live')) {
                                            return 'trays';
                                        }
                                        
                                        return 'g';
                                    })
                                    ->hint(function (Forms\Get $get): string {
                                        $packagingId = $get('packaging_type_id');
                                        if (!$packagingId) {
                                            return 'Package-free variation';
                                        }
                                        
                                        $packaging = \App\Models\PackagingType::find($packagingId);
                                        if ($packaging && $packaging->capacity_weight) {
                                            return 'Package capacity: ' . $packaging->capacity_weight . 'g';
                                        }
                                        return '';
                                    })
                                    ->required(fn (Forms\Get $get): bool => !$get('is_global'))
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-generate name if not manually overridden and for bulk types
                                        if (!$get('manual_name_override') && $get('pricing_type') === 'bulk' && !$get('packaging_type_id')) {
                                            self::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                                        }
                                    }),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU / Barcode')
                                    ->placeholder('Optional product code')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Settings Section
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Enable this price variation')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Price')
                                    ->helperText('Use as the default price for this product')
                                    ->default(false)
                                    ->visible(fn (Forms\Get $get): bool => !$get('is_global'))
                                    ->disabled(fn (Forms\Get $get): bool => $get('is_global')),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Notes')
                            ->placeholder('Optional notes about this price variation...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Generate variation name based on packaging and pricing type
     */
    protected static function generateVariationName($packagingId, $pricingType, callable $set, callable $get): void
    {
        $parts = [];
        $pricingUnit = $get('pricing_unit');
        
        // Get packaging info if selected
        if ($packagingId) {
            $packaging = \App\Models\PackagingType::find($packagingId);
            if ($packaging) {
                // Add packaging type
                $parts[] = $packaging->name;
                
                // Add size if available and not already in packaging name
                if ($packaging->capacity_volume && $packaging->volume_unit) {
                    $sizeString = $packaging->capacity_volume . $packaging->volume_unit;
                    if (!str_contains(strtolower($packaging->name), strtolower($sizeString))) {
                        $parts[] = '(' . $sizeString . ')';
                    }
                } elseif ($packaging->capacity_weight) {
                    // Convert grams to oz for display
                    $oz = round($packaging->capacity_weight / 28.35, 1);
                    $ozString = $oz . 'oz';
                    if (!str_contains(strtolower($packaging->name), strtolower($ozString))) {
                        $parts[] = '(' . $ozString . ')';
                    }
                }
            }
        } else {
            // Handle package-free variations
            $fillWeight = $get('fill_weight');
            if ($fillWeight || $pricingUnit !== 'per_item') {
                if (str_contains(strtolower($pricingType ?? ''), 'bulk')) {
                    // For bulk, show pricing unit
                    if ($pricingUnit && $pricingUnit !== 'per_item') {
                        $unitLabels = [
                            'per_g' => 'per gram',
                            'per_kg' => 'per kg',
                            'per_lb' => 'per lb',
                            'per_oz' => 'per oz',
                        ];
                        $parts[] = 'Bulk';
                        $parts[] = '(' . ($unitLabels[$pricingUnit] ?? '') . ')';
                    } elseif ($fillWeight) {
                        // Show total weight if no unit pricing
                        $lbs = round($fillWeight / 453.592, 2);
                        $parts[] = 'Bulk';
                        $parts[] = '(' . $lbs . 'lb)';
                    }
                } else {
                    // For other package-free, just use type
                    $parts[] = 'Package-Free';
                }
            }
        }
        
        // Add pricing unit indicator for unit-based pricing
        if ($pricingUnit && $pricingUnit !== 'per_item' && !str_contains(strtolower($pricingType ?? ''), 'bulk')) {
            $unitLabels = [
                'per_g' => '/g',
                'per_kg' => '/kg',
                'per_lb' => '/lb',
                'per_oz' => '/oz',
            ];
            if (isset($unitLabels[$pricingUnit])) {
                $parts[] = $unitLabels[$pricingUnit];
            }
        }
        
        // Add pricing type abbreviation
        if ($pricingType) {
            $abbreviations = [
                'retail' => '(Ret)',
                'wholesale' => '(Wh)',
                'bulk' => '(Bulk)',
                'special' => '(Spec)',
                'custom' => '',
            ];
            
            $abbr = $abbreviations[$pricingType] ?? '';
            if ($abbr) {
                $parts[] = $abbr;
            }
        }
        
        // Set the generated name
        $generatedName = implode(' ', $parts);
        if ($generatedName) {
            $set('name', $generatedName);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Global Template'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('packagingType.name')
                    ->label('Packaging Type')
                    ->sortable()
                    ->placeholder('Package-Free')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU/UPC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fill_weight')
                    ->label('Weight/Qty')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->is_global && !$state) {
                            return 'Template';
                        }
                        
                        if (!$state) {
                            return 'N/A';
                        }
                        
                        // Handle package-free variations (no packaging type)
                        if (!$record->packagingType) {
                            // Determine format based on variation name
                            $name = strtolower($record->name);
                            if (str_contains($name, 'tray') || str_contains($name, 'live')) {
                                return $state . ' tray' . ($state != 1 ? 's' : '');
                            }
                            if (str_contains($name, 'bulk') || str_contains($name, 'lb') || str_contains($name, 'pound')) {
                                return $state . 'g (' . number_format($state / 454, 2) . 'lb)';
                            }
                            if (str_contains($name, 'each') || str_contains($name, 'unit') || str_contains($name, 'piece')) {
                                return $state . ' unit' . ($state != 1 ? 's' : '');
                            }
                            // Default for package-free
                            return $state . ' units';
                        }
                        
                        // Special formatting for different packaging types
                        if ($record->packagingType) {
                            $packagingName = strtolower($record->packagingType->name);
                            if (str_contains($packagingName, 'live') || str_contains($packagingName, 'tray')) {
                                return $state . ' tray' . ($state != 1 ? 's' : '');
                            }
                            if (str_contains($packagingName, 'bulk')) {
                                return $state . 'g (' . number_format($state / 454, 2) . 'lb)';
                            }
                        }
                        
                        return $state . 'g';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Template')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Product'),
                Tables\Filters\SelectFilter::make('packagingType')
                    ->relationship('packagingType', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Packaging Type'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Price'),
                Tables\Filters\TernaryFilter::make('is_global')
                    ->label('Global Templates'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit price variation'),
                Tables\Actions\Action::make('apply_template')
                    ->label('Apply to Product')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_global)
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Variation Name')
                            ->required()
                            ->default(fn ($record) => $record->name),
                        Forms\Components\TextInput::make('fill_weight')
                            ->label('Fill Weight (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->helperText('Specify the actual fill weight for this product')
                            ->required(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255)
                            ->default(fn ($record) => $record->sku),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Custom Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->default(fn ($record) => $record->price)
                                    ->required()
                                    ->helperText(fn ($record) => 'Template price: $' . number_format($record->price, 2)),
                                Forms\Components\Placeholder::make('price_comparison')
                                    ->label('Price Override')
                                    ->content('Enter a custom price above to override the template pricing')
                                    ->extraAttributes(['class' => 'prose text-sm']),
                            ]),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Make this the default price for the product')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function ($record, array $data) {
                        // Create a new product-specific variation based on the template
                        \App\Models\PriceVariation::create([
                            'product_id' => $data['product_id'],
                            'packaging_type_id' => $record->packaging_type_id,
                            'name' => $data['name'],
                            'sku' => $data['sku'],
                            'fill_weight' => $data['fill_weight_grams'],
                            'price' => $data['price'],
                            'is_default' => $data['is_default'],
                            'is_global' => false,
                            'is_active' => $data['is_active'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Template Applied Successfully')
                            ->body('The template has been applied to the selected product.')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Apply this template to a specific product'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete price variation')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Price Variation')
                    ->modalDescription('Are you sure you want to delete this price variation? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Price Variations')
                        ->modalDescription('Are you sure you want to delete the selected price variations? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
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
            'index' => Pages\ListPriceVariations::route('/'),
            'create' => Pages\CreatePriceVariation::route('/create'),
            'edit' => Pages\EditPriceVariation::route('/{record}/edit'),
        ];
    }
}
