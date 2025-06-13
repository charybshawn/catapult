<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use App\Filament\Resources\ProductInventoryResource;
use App\Forms\Components\ProductInventoryVariationsTable;
use App\Models\Product;
use App\Models\ProductInventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class BulkCreateInventory extends Page
{
    protected static string $resource = ProductInventoryResource::class;

    protected static string $view = 'filament.resources.product-inventory-resource.pages.bulk-create-inventory';

    protected static ?string $title = 'Bulk Create Inventory';

    protected static ?string $navigationLabel = 'Bulk Create';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Selection')
                    ->description('Select a product to create inventory for all its price variations')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->options(fn () => \App\Models\Product::where('active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Clear the variations data when product changes
                                $set('inventory_data', null);
                            })
                            ->helperText('Select the product you want to create inventory for'),
                    ]),

                Forms\Components\Section::make('Inventory Data')
                    ->schema([
                        ProductInventoryVariationsTable::make('inventory_data')
                            ->productId(fn (Forms\Get $get): ?int => $get('product_id'))
                            ->visible(fn (Forms\Get $get): bool => !empty($get('product_id')))
                    ])
                    ->visible(fn (Forms\Get $get): bool => !empty($get('product_id'))),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_inventory')
                ->label('Create Inventory Entries')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    Forms\Components\Section::make('Batch Information')
                        ->description('Common information shared by all inventory entries')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('batch_number')
                                        ->label('Batch Number')
                                        ->default(fn (): string => $this->getDefaultBatchNumber())
                                        ->helperText('Auto-generated or enter custom'),
                                    Forms\Components\DatePicker::make('production_date')
                                        ->label('Production Date')
                                        ->default(now())
                                        ->required(),
                                    Forms\Components\TextInput::make('location')
                                        ->label('Storage Location')
                                        ->placeholder('e.g., Warehouse A, Shelf 3'),
                                ]),
                        ]),
                    Forms\Components\Section::make('Inventory Details')
                        ->description('Enter quantities and details for each price variation')
                        ->schema([
                            Forms\Components\Repeater::make('variations')
                                ->schema([
                                    Forms\Components\Hidden::make('price_variation_id'),
                                    Forms\Components\Placeholder::make('variation_info')
                                        ->content(function (Forms\Get $get): string {
                                            $variationId = $get('price_variation_id');
                                            if ($variationId) {
                                                $variation = \App\Models\PriceVariation::with('packagingType')->find($variationId);
                                                if ($variation) {
                                                    $packaging = $variation->packagingType?->display_name ?? 'Package-Free';
                                                    $weight = $variation->fill_weight_grams ? $variation->fill_weight_grams . 'g' : '-';
                                                    return $variation->name . ' (' . $packaging . ') - ' . $weight . ' - $' . number_format($variation->price, 2);
                                                }
                                            }
                                            return 'Unknown variation';
                                        }),
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\TextInput::make('quantity')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->default(0)
                                                ->required()
                                                ->suffix('units'),
                                            Forms\Components\TextInput::make('cost_per_unit')
                                                ->label('Cost per Unit')
                                                ->numeric()
                                                ->prefix('$')
                                                ->step(0.01)
                                                ->minValue(0)
                                                ->default(0),
                                            Forms\Components\TextInput::make('lot_number')
                                                ->label('Lot Number')
                                                ->placeholder('Optional'),
                                            Forms\Components\DatePicker::make('expiration_date')
                                                ->label('Expiration Date')
                                                ->after('production_date'),
                                        ]),
                                ])
                                ->default(fn (): array => $this->getVariationDefaults())
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->createInventoryEntries($data);
                })
                ->visible(fn (): bool => !empty($this->data['product_id']))
                ->modalWidth('7xl'),
        ];
    }

    protected function getDefaultBatchNumber(): string
    {
        if (empty($this->data['product_id'])) {
            return '';
        }

        $product = Product::find($this->data['product_id']);
        return $product?->getNextBatchNumber() ?? '';
    }

    protected function getVariationDefaults(): array
    {
        if (empty($this->data['product_id'])) {
            return [];
        }

        $product = Product::with(['priceVariations.packagingType'])->find($this->data['product_id']);
        
        if (!$product) {
            return [];
        }

        return $product->priceVariations()
            ->where('is_active', true)
            ->get()
            ->map(function ($variation) {
                return [
                    'price_variation_id' => $variation->id,
                    'quantity' => 0,
                    'cost_per_unit' => 0,
                    'lot_number' => '',
                    'expiration_date' => null,
                ];
            })
            ->toArray();
    }

    public function createInventoryEntries(array $modalData): void
    {
        $productData = $this->form->getState();
        
        if (empty($productData['product_id'])) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Please select a product first.')
                ->send();
            return;
        }

        $product = Product::find($productData['product_id']);
        if (!$product) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Selected product not found.')
                ->send();
            return;
        }

        $variationsWithQuantity = collect($modalData['variations'])
            ->filter(function ($variation) {
                return !empty($variation['quantity']) && $variation['quantity'] > 0;
            });

        if ($variationsWithQuantity->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('No Quantities Entered')
                ->body('Please enter quantities greater than 0 for at least one price variation.')
                ->send();
            return;
        }

        $createdCount = 0;
        $errors = [];

        DB::beginTransaction();
        
        try {
            foreach ($variationsWithQuantity as $index => $variationData) {
                // Find the actual price variation
                $priceVariation = $product->priceVariations()->find($variationData['price_variation_id']);
                
                if (!$priceVariation) {
                    $errors[] = "Price variation not found for index {$index}";
                    continue;
                }

                // Generate batch number for this variation
                $batchNumber = $modalData['batch_number'] ?? $product->getNextBatchNumber();
                if (count($variationsWithQuantity) > 1) {
                    // Append variation identifier if multiple variations
                    $batchNumber .= '-' . strtoupper(substr($priceVariation->name, 0, 3));
                }

                // Create inventory entry
                ProductInventory::create([
                    'product_id' => $product->id,
                    'price_variation_id' => $priceVariation->id,
                    'batch_number' => $batchNumber,
                    'lot_number' => $variationData['lot_number'] ?? null,
                    'quantity' => $variationData['quantity'],
                    'reserved_quantity' => 0,
                    'cost_per_unit' => $variationData['cost_per_unit'] ?? 0,
                    'production_date' => $modalData['production_date'] ?? now(),
                    'expiration_date' => $variationData['expiration_date'] ?? null,
                    'location' => $modalData['location'] ?? null,
                    'status' => 'active',
                    'notes' => "Bulk created for {$priceVariation->name} variation",
                ]);

                $createdCount++;
            }

            DB::commit();

            if ($createdCount > 0) {
                Notification::make()
                    ->success()
                    ->title('Inventory Created Successfully')
                    ->body("Created {$createdCount} inventory " . ($createdCount === 1 ? 'entry' : 'entries') . " for {$product->name}.")
                    ->send();

                // Reset form
                $this->form->fill();
                
                // Redirect to inventory index
                $this->redirect(ProductInventoryResource::getUrl('index'));
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            Notification::make()
                ->danger()
                ->title('Error Creating Inventory')
                ->body('An error occurred: ' . $e->getMessage())
                ->send();
        }

        if (!empty($errors)) {
            Notification::make()
                ->warning()
                ->title('Some Issues Occurred')
                ->body('Some entries could not be created: ' . implode(', ', $errors))
                ->send();
        }
    }
}