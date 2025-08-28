<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Filament\Resources\BaseResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use App\Models\PriceVariation;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Models\PackagingType;
use Filament\Forms;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
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
                                Action::make('reset_to_auto')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Reset to auto-generated name')
                                    ->action(function (callable $set, callable $get) {
                                        $set('is_name_manual', false);
                                        self::generateVariationName($get('packaging_type_id'), 'retail', $set, $get);
                                    })
                            ),
                        
                        Hidden::make('is_name_manual')
                            ->default(false),
                        
                        Hidden::make('generated_name'),
                        Select::make('packaging_type_id')
                            ->relationship('packagingType', 'name')
                            ->label('Packaging Type')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Only auto-generate for new records, not when editing existing ones
                                if (!$get('id')) {
                                    self::generateVariationName($state, 'retail', $set, $get);
                                }
                            }),
                    ]),
                    
                Grid::make(3)
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('fill_weight')
                            ->label('Fill Weight / Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g / trays')
                            ->helperText('Fill weight in grams (packaged) or quantity (live trays, bulk by weight)')
                            ->columnSpan(1),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Only auto-generate for new records, not when editing existing ones
                                if (!$get('id')) {
                                    self::generateVariationName($get('packaging_type_id'), 'retail', $set, $get);
                                }
                            }),
                    ]),

                Grid::make(3)
                    ->schema([
                        Toggle::make('is_default')
                            ->label('Default Price')
                            ->helperText('Only one variation can be the default.')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive variations won\'t be used for pricing.')
                            ->default(true),
                        Toggle::make('is_global')
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
                BaseResource::getNameColumn(),
                TextColumn::make('packagingType.name')
                    ->label('Packaging Type')
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('sku')
                    ->label('SKU/UPC')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('fill_weight')
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
                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('packagingType')
                    ->relationship('packagingType', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Packaging Type'),
                TernaryFilter::make('is_default')
                    ->label('Default Price'),
                TernaryFilter::make('is_global')
                    ->label('Global Templates'),
                TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data) {
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
                Action::make('apply_template')
                    ->label('Apply Template')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->schema([
                        Select::make('template_id')
                            ->label('Choose Template')
                            ->options(function () {
                                return PriceVariation::where('is_global', true)
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
                                    $template = PriceVariation::find($state);
                                    if ($template) {
                                        $set('name', $template->name);
                                        $set('sku', $template->sku);
                                        $set('price', $template->price);
                                        $set('packaging_type_id', $template->packaging_type_id);
                                    }
                                }
                            }),
                        TextInput::make('name')
                            ->label('Variation Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('fill_weight')
                            ->label('Fill Weight (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->helperText('Specify the actual fill weight for this product')
                            ->required(),
                        TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('price')
                                    ->label('Custom Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('Enter custom price or leave as template default'),
                                Placeholder::make('template_price_info')
                                    ->label('Template Info')
                                    ->content('Template price will be shown here when a template is selected')
                                    ->reactive(),
                            ]),
                        Toggle::make('is_default')
                            ->label('Make this the default price for the product')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $template = PriceVariation::find($data['template_id']);
                        
                        // Create a new product-specific variation based on the template
                        PriceVariation::create([
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
            ->recordActions([
                EditAction::make(),
                Action::make('set_default')
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
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Price Variation')
                    ->modalDescription('Are you sure you want to delete this price variation? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Price Variations')
                        ->modalDescription('Are you sure you want to delete the selected price variations? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                    BulkAction::make('activate')
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
                    BulkAction::make('deactivate')
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
                CreateAction::make()
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
            $packaging = PackagingType::find($packagingId);
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