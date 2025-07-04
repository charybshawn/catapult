<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class PriceVariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'priceVariations';

    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?string $title = 'Price Variations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Variation Name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Mark as manual if user manually edits the name
                                if ($state !== 'Auto-generated' && $state !== $get('generated_name')) {
                                    $set('is_name_manual', true);
                                }
                            })
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('reset_to_auto')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Reset to auto-generated name')
                                    ->action(function (callable $set, callable $get) {
                                        $set('is_name_manual', false);
                                        self::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                                    })
                            ),
                        
                        Forms\Components\Hidden::make('is_name_manual')
                            ->default(false),
                        
                        Forms\Components\Hidden::make('generated_name'),
                        Forms\Components\Select::make('packaging_type_id')
                            ->relationship('packagingType', 'name')
                            ->label('Packaging Type')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::generateVariationName($state, $get('pricing_type'), $set, $get);
                            }),
                    ]),
                    
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('fill_weight')
                            ->label('Fill Weight / Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g / trays')
                            ->helperText('Fill weight in grams (packaged) or quantity (live trays, bulk by weight)')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                            }),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Price')
                            ->helperText('Only one variation can be the default.')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive variations won\'t be used for pricing.')
                            ->default(true),
                        Forms\Components\Toggle::make('is_global')
                            ->label('Make Global')
                            ->helperText('When enabled, this price variation template will be available for all products')
                            ->default(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('packagingType.name')
                    ->label('Packaging Type')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU/UPC')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fill_weight')
                    ->label('Weight/Qty')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return 'N/A';
                        }
                        
                        // Special formatting for different packaging types
                        if ($record->packagingType) {
                            if ($record->packagingType->name === 'Live Tray') {
                                return $state . ' tray' . ($state != 1 ? 's' : '');
                            }
                            if ($record->packagingType->name === 'Bulk') {
                                return $state . 'g (' . number_format($state / 454, 2) . 'lb)';
                            }
                        }
                        
                        return $state . 'g';
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Set the product_id to the owner record
                        $data['product_id'] = $this->ownerRecord->id;
                        
                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        // Ensure only one default price variation exists
                        $defaultCount = $livewire->ownerRecord->priceVariations()->where('is_default', true)->count();
                        
                        if ($defaultCount > 1) {
                            // Keep only the most recently created one as default
                            $mostRecentDefault = $livewire->ownerRecord->priceVariations()
                                ->where('is_default', true)
                                ->latest()
                                ->first();
                                
                            $livewire->ownerRecord->priceVariations()
                                ->where('is_default', true)
                                ->where('id', '!=', $mostRecentDefault->id)
                                ->update(['is_default' => false]);
                        }
                    }),
                Tables\Actions\Action::make('apply_template')
                    ->label('Apply Template')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('Choose Template')
                            ->options(function () {
                                return \App\Models\PriceVariation::where('is_global', true)
                                    ->where('is_active', true)
                                    ->with('packagingType')
                                    ->get()
                                    ->mapWithKeys(function ($template) {
                                        $label = $template->name;
                                        if ($template->packagingType) {
                                            $label .= ' (' . $template->packagingType->name . ')';
                                        }
                                        $label .= ' - $' . number_format($template->price, 2);
                                        return [$template->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = \App\Models\PriceVariation::find($state);
                                    if ($template) {
                                        $set('name', $template->name);
                                        $set('sku', $template->sku);
                                        $set('price', $template->price);
                                        $set('packaging_type_id', $template->packaging_type_id);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Variation Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('fill_weight')
                            ->label('Fill Weight (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->helperText('Specify the actual fill weight for this product')
                            ->required(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Custom Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('Enter custom price or leave as template default'),
                                Forms\Components\Placeholder::make('template_price_info')
                                    ->label('Template Info')
                                    ->content('Template price will be shown here when a template is selected')
                                    ->reactive(),
                            ]),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Make this the default price for the product')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $template = \App\Models\PriceVariation::find($data['template_id']);
                        
                        // Create a new product-specific variation based on the template
                        \App\Models\PriceVariation::create([
                            'product_id' => $livewire->ownerRecord->id,
                            'packaging_type_id' => $template->packaging_type_id,
                            'name' => $data['name'],
                            'sku' => $data['sku'],
                            'fill_weight' => $data['fill_weight_grams'],
                            'price' => $data['price'],
                            'is_default' => $data['is_default'],
                            'is_global' => false,
                            'is_active' => $data['is_active'],
                        ]);
                        
                        Notification::make()
                            ->title('Template Applied Successfully')
                            ->body('The template has been applied to this product.')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Apply a global pricing template to this product'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_default')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->hidden(fn ($record) => $record->is_default)
                    ->requiresConfirmation()
                    ->modalHeading('Set as Default Price Variation')
                    ->modalDescription('This will make this variation the default price and remove the default status from any other variations.')
                    ->action(function ($record, RelationManager $livewire) {
                        // Set this as the default and remove default from all others
                        $record->update(['is_default' => true]);
                        
                        $livewire->ownerRecord->priceVariations()
                            ->where('id', '!=', $record->id)
                            ->where('is_default', true)
                            ->update(['is_default' => false]);
                            
                        Notification::make()
                            ->title('Default price variation updated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
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
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Price Variations')
                        ->modalDescription('Are you sure you want to activate the selected price variations?')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                            
                            Notification::make()
                                ->title('Price variations activated')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Price Variations')
                        ->modalDescription('Are you sure you want to deactivate the selected price variations? Note: Default variations cannot be deactivated.')
                        ->action(function ($records, RelationManager $livewire) {
                            $defaultIds = $records->where('is_default', true)->pluck('id');
                            
                            // Don't deactivate default variations
                            if ($defaultIds->count() > 0) {
                                Notification::make()
                                    ->title('Cannot deactivate default price variation')
                                    ->body('Please set another variation as default first.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                            
                            Notification::make()
                                ->title('Price variations deactivated')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No price variations')
            ->emptyStateDescription('Create price variations to set different prices based on packaging, customer type, or quantity.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create First Price Variation'),
            ]);
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