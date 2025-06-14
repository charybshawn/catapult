<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListProductInventories extends ListRecords
{
    protected static string $resource = ProductInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Inventory'),
            Actions\Action::make('rebuild_entries')
                ->label('Rebuild Inventory Entries')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Rebuild Inventory Entries')
                ->modalDescription('This will create inventory entries for any active price variations that are missing them. Existing inventory entries will NOT be modified or deleted.')
                ->modalSubmitActionLabel('Rebuild Entries')
                ->action(function () {
                    $this->rebuildInventoryEntries();
                })
                ->tooltip('Create missing inventory entries for active price variations'),
        ];
    }

    public function rebuildInventoryEntries(): void
    {
        $startTime = microtime(true);
        
        // Get all active products with their price variations and existing inventory
        $products = \App\Models\Product::with(['priceVariations', 'inventories'])
            ->where('active', true)
            ->get();

        $totalCreated = 0;
        $productsProcessed = 0;
        $skippedProducts = 0;

        foreach ($products as $product) {
            $activeVariations = $product->priceVariations()
                ->where('is_active', true)
                ->get();

            if ($activeVariations->isEmpty()) {
                $skippedProducts++;
                continue;
            }

            $createdForProduct = 0;

            foreach ($activeVariations as $variation) {
                $existingInventory = $product->inventories()
                    ->where('price_variation_id', $variation->id)
                    ->first();

                if (!$existingInventory) {
                    // Create new inventory entry
                    $product->inventories()->create([
                        'price_variation_id' => $variation->id,
                        'batch_number' => $product->getNextBatchNumber() . '-' . strtoupper(substr($variation->name, 0, 3)),
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'cost_per_unit' => 0,
                        'production_date' => now(),
                        'expiration_date' => null,
                        'location' => null,
                        'status' => 'active',
                        'notes' => "Created via rebuild process for {$variation->name} variation",
                    ]);
                    $createdForProduct++;
                }
            }

            if ($createdForProduct > 0) {
                $productsProcessed++;
                $totalCreated += $createdForProduct;
            }
        }

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        // Create detailed notification
        $title = $totalCreated > 0 ? 'Inventory Entries Rebuilt' : 'Rebuild Complete - No Changes Needed';
        $body = "✅ Created {$totalCreated} new inventory entries\n";
        $body .= "📦 Processed {$productsProcessed} products\n";
        
        if ($skippedProducts > 0) {
            $body .= "⏭️ Skipped {$skippedProducts} products (no active variations)\n";
        }
        
        $body .= "⚡ Completed in {$executionTime}ms";

        \Filament\Notifications\Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->duration(8000)
            ->send();

        // Log the operation for audit purposes
        Log::info('Inventory entries rebuild completed', [
            'user_id' => auth()->id(),
            'total_created' => $totalCreated,
            'products_processed' => $productsProcessed,
            'skipped_products' => $skippedProducts,
            'execution_time_ms' => $executionTime,
        ]);
    }
}
