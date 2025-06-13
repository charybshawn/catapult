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
                            ->relationship('product', 'name')
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
                ->action('createInventoryEntries')
                ->visible(fn (): bool => !empty($this->data['product_id'])),
        ];
    }

    public function createInventoryEntries(): void
    {
        $data = $this->form->getState();
        
        if (empty($data['product_id'])) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Please select a product first.')
                ->send();
            return;
        }

        if (empty($data['inventory_data']['variations'])) {
            Notification::make()
                ->warning()
                ->title('No Data')
                ->body('Please enter inventory quantities for at least one price variation.')
                ->send();
            return;
        }

        $product = Product::find($data['product_id']);
        if (!$product) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Selected product not found.')
                ->send();
            return;
        }

        $inventoryData = $data['inventory_data'];
        $variationsWithQuantity = collect($inventoryData['variations'])
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
                $priceVariation = $product->priceVariations()->find($variationData['id']);
                
                if (!$priceVariation) {
                    $errors[] = "Price variation not found for index {$index}";
                    continue;
                }

                // Generate batch number for this variation
                $batchNumber = $inventoryData['batch_number'] ?? $product->getNextBatchNumber();
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
                    'production_date' => $inventoryData['production_date'] ?? now(),
                    'expiration_date' => $variationData['expiration_date'] ?? null,
                    'location' => $inventoryData['location'] ?? null,
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