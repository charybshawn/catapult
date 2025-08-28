<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Models\Product;
use Filament\Notifications\Notification;
use App\Filament\Resources\ProductInventoryResource;
use App\Filament\Resources\ProductInventoryResource\Widgets\ProductInventoryStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * ListProductInventories Page for Agricultural Inventory Overview and Management
 * 
 * Provides comprehensive listing of agricultural product inventory with automated
 * inventory entry creation functionality. Includes specialized rebuild capability
 * for ensuring all active price variations have corresponding inventory entries.
 * Critical for maintaining complete inventory visibility in microgreens operations.
 * 
 * @filament_page List page for ProductInventoryResource with inventory management tools
 * @business_domain Agricultural product inventory with automated entry creation
 * @extends ListRecords Standard Filament list page with agricultural business logic
 * 
 * @inventory_automation Rebuild functionality creates missing inventory entries automatically
 * @agricultural_focus Handles microgreens inventory with batch tracking and expiration management
 * @business_logic Ensures all active price variations have inventory tracking entries
 * 
 * @widget_integration ProductInventoryStats widget for inventory overview dashboard
 * @related_models Product, PriceVariation, ProductInventory for complete inventory context
 * @audit_logging Detailed operation logging for agricultural business compliance
 */
class ListProductInventories extends ListRecords
{
    protected static string $resource = ProductInventoryResource::class;
    
    /**
     * Get header widgets for agricultural inventory dashboard overview.
     * 
     * Provides ProductInventoryStats widget displaying key inventory metrics
     * including stock levels, low stock alerts, and expiration tracking
     * essential for agricultural inventory management.
     * 
     * @return array Header widgets for inventory dashboard display
     * @agricultural_metrics Stock levels, expiration dates, low stock alerts
     * @dashboard_integration Inventory overview widget for operational awareness
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ProductInventoryStats::class,
        ];
    }

    /**
     * Get header actions for agricultural inventory management.
     * 
     * Provides create action for new inventory entries and specialized rebuild
     * functionality to ensure all active price variations have corresponding
     * inventory entries. Essential for maintaining complete inventory tracking.
     * 
     * @return array Header actions including create and rebuild functionality
     * @agricultural_operations Manual inventory creation and automated entry generation
     * @inventory_integrity Rebuild ensures no active variations lack inventory tracking
     * @business_safety Confirmation dialogs for potentially impactful operations
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Inventory'),
            Action::make('rebuild_entries')
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

    /**
     * Rebuild inventory entries for agricultural products with missing inventory tracking.
     * 
     * Systematically creates inventory entries for all active price variations that
     * lack corresponding inventory records. Essential for ensuring complete inventory
     * visibility across all agricultural products and variations. Includes performance
     * monitoring, detailed notifications, and audit logging.
     * 
     * @return void Creates missing inventory entries with performance tracking
     * @agricultural_logic Processes products with active price variations requiring inventory
     * @batch_operations Efficient bulk processing with batch number auto-generation
     * @audit_compliance Detailed logging and notifications for business transparency
     * @performance_monitoring Execution time tracking for large inventory operations
     */
    public function rebuildInventoryEntries(): void
    {
        $startTime = microtime(true);
        
        // Get all active products with their price variations and existing inventory
        $products = Product::with(['priceVariations', 'inventories'])
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

        Notification::make()
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
