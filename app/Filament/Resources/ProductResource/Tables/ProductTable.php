<?php

namespace App\Filament\Resources\ProductResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use App\Actions\Product\CloneProductAction;
use App\Filament\Resources\ProductResource;
use Exception;
use Filament\Actions\DeleteAction;
use App\Actions\Product\ValidateProductDeletionAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Actions\Product\BulkUpdateProductStatusAction;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table configuration class for agricultural product management interface
 * with comprehensive display, filtering, and bulk operation capabilities.
 *
 * This class handles the sophisticated table presentation required for agricultural
 * product catalog management, including variety type visualization, packaging
 * availability display, and agricultural workflow-specific actions like product
 * cloning and inventory-aware deletion validation.
 *
 * @filament_table_class Dedicated table builder for ProductResource
 * @business_domain Agricultural product catalog with variety and packaging display
 * @agricultural_concepts Single varieties, product mixes, packaging configurations
 * 
 * @table_features
 * - Variety type column with cultivar name display for agricultural context
 * - Packaging availability with visual badges for different container types
 * - Category filtering for agricultural product organization
 * - Status filtering (active, store visibility) for workflow management
 * 
 * @agricultural_display
 * - Dynamic variety type showing actual variety names or "Product Mix"
 * - Packaging badges indicating available container sizes and types
 * - Image thumbnails for customer-facing product identification
 * - Status indicators for agricultural workflow states
 * 
 * @bulk_operations
 * - Product cloning for creating similar agricultural varieties
 * - Status updates (activate/deactivate, show/hide in store) for seasonal changes
 * - Deletion with agricultural business rule validation (active crop prevention)
 * - Export functionality for external agricultural planning systems
 * 
 * @performance_optimization
 * - Eager loading of relationships prevents N+1 queries
 * - Efficient packaging display with single query for variations
 * - Optimized column rendering for large product catalogs
 * 
 * @business_rules_enforcement
 * - Deletion validation prevents removal of products with active crops
 * - Clone operations maintain agricultural context while creating new variations
 * - Status changes respect agricultural workflow dependencies
 */
class ProductTable
{
    /**
     * Get table columns optimized for agricultural product catalog display.
     *
     * Provides a comprehensive column set showing essential agricultural product
     * information including variety types, packaging availability, and status
     * indicators needed for farm management and customer service workflows.
     *
     * @return array Column definitions with agricultural product context
     * @agricultural_display Variety type, packaging badges, category classification
     * @business_information Name, image, status indicators for workflow management
     * @performance_optimized Efficient rendering for large agricultural product catalogs
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable(),
            ImageColumn::make('default_photo')
                ->label('Image')
                ->circular(),
            TextColumn::make('category.name')
                ->label('Category')
                ->sortable(),
            static::getVarietyTypeColumn(),
            IconColumn::make('active')
                ->boolean()
                ->sortable(),
            IconColumn::make('is_visible_in_store')
                ->label('In Store')
                ->boolean()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            static::getAvailablePackagingColumn(),
        ];
    }

    /**
     * Get table filters for agricultural product catalog management.
     *
     * Provides filtering capabilities essential for agricultural product organization
     * including category-based filtering, product type classification (single variety
     * vs. mix), and business status filtering for workflow management.
     *
     * @return array Filter definitions with agricultural product context
     * @agricultural_filtering Category and variety type filters for product organization
     * @business_workflow Status filters for availability and visibility management
     * @user_experience Streamlined filtering for large agricultural product catalogs
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('category')
                ->relationship('category', 'name'),
            static::getVarietyTypeFilter(),
            TernaryFilter::make('active'),
            TernaryFilter::make('is_visible_in_store')
                ->label('Visible in Store'),
        ];
    }

    /**
     * Get row-level actions for agricultural product management.
     *
     * Provides essential actions for individual product management including
     * viewing detailed agricultural information, editing product configurations,
     * cloning for creating similar varieties, and deletion with agricultural
     * business rule validation.
     *
     * @return array Action definitions with agricultural workflow integration
     * @agricultural_actions Clone products for variety creation, view agricultural details
     * @business_operations Edit configurations, delete with crop validation
     * @user_interface Grouped actions with tooltips and agricultural context
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->tooltip('View record'),
                EditAction::make()
                    ->tooltip('Edit record'),
                static::getCloneAction(),
                static::getDeleteAction(),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get bulk operations for agricultural product catalog management.
     *
     * Provides efficient bulk operations essential for agricultural product
     * management including status updates for seasonal changes, visibility
     * management for customer-facing systems, and validated bulk deletion
     * with agricultural workflow protection.
     *
     * @return array Bulk action definitions with agricultural context
     * @agricultural_operations Seasonal activation/deactivation, visibility management
     * @business_efficiency Bulk status changes for product catalog maintenance
     * @workflow_protection Validated deletion prevents agricultural workflow disruption
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getDeleteBulkAction(),
                static::getActivateBulkAction(),
                static::getDeactivateBulkAction(),
                static::getShowInStoreBulkAction(),
                static::getHideFromStoreBulkAction(),
            ]),
        ];
    }

    /**
     * Get variety type column with intelligent agricultural product classification.
     *
     * Displays the agricultural product type with context-sensitive information:
     * - Single varieties show actual cultivar names for agricultural identification
     * - Product mixes show mix names for multi-variety product recognition
     * - Fallback to common names when cultivar information is unavailable
     * - "No Variety" indicator for unassigned products requiring configuration
     *
     * @return TextColumn Variety type display with agricultural context
     * @agricultural_intelligence Shows meaningful variety information for crop planning
     * @business_context Helps identify products needing variety assignment
     * @user_experience Clear visual indication of product agricultural classification
     */
    protected static function getVarietyTypeColumn(): TextColumn
    {
        return TextColumn::make('variety_type')
            ->label('Type')
            ->getStateUsing(function ($record): string {
                if ($record->master_seed_catalog_id) {
                    $catalog = $record->masterSeedCatalog;
                    if (!$catalog) {
                        return 'Single Variety';
                    }
                    
                    // Show cultivar name if available, otherwise just common name
                    if ($catalog->cultivar && $catalog->cultivar->cultivar_name) {
                        return $catalog->cultivar->cultivar_name;
                    }
                    
                    return $catalog->common_name;
                } elseif ($record->product_mix_id) {
                    return $record->productMix->name ?? 'Product Mix';
                }
                return 'No Variety';
            })
            ->searchable(false)
            ->sortable(false)
            ->toggleable();
    }

    /**
     * Get packaging availability column with visual agricultural packaging display.
     *
     * Creates an attractive badge-based display showing available packaging options
     * for each agricultural product. Only displays actual product-specific packaging
     * (not potential templates) to give accurate availability information for
     * order processing and customer service workflows.
     *
     * @return TextColumn Packaging display with visual badges for agricultural products
     * @agricultural_packaging Shows actual container types, sizes, and configurations
     * @business_accuracy Only displays confirmed product packaging, not potential options
     * @visual_design Badge-based display for quick packaging identification
     * @performance_optimized Single query to gather packaging information efficiently
     */
    protected static function getAvailablePackagingColumn(): TextColumn
    {
        return TextColumn::make('available_packaging')
            ->label('Packaging')
            ->html()
            ->getStateUsing(function ($record): string {
                // Get only product-specific price variations with packaging
                $productPackaging = $record->priceVariations()
                    ->whereNotNull('packaging_type_id')
                    ->with('packagingType')
                    ->get()
                    ->pluck('packagingType.name')
                    ->unique();
                
                // Only show actual product packaging, not potential templates
                $packaging = $productPackaging;
                
                if ($packaging->isEmpty()) {
                    return '<span class="text-gray-400">No packaging</span>';
                }
                
                // Create badges for actual product packaging
                $badges = $packaging->map(function ($name) {
                    return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . $name . '</span>';
                })->join(' ');
                
                return $badges;
            })
            ->searchable(false)
            ->sortable(false);
    }

    /**
     * Get variety type filter for agricultural product classification.
     *
     * Provides filtering by agricultural product type (single variety, product mix,
     * or unassigned) enabling quick catalog organization and identification of
     * products requiring variety assignment or configuration completion.
     *
     * @return SelectFilter Product type classification filter
     * @agricultural_classification Single variety, mix, or unassigned product filtering
     * @business_workflow Helps identify products needing agricultural configuration
     * @query_optimization Efficient database queries for product type classification
     */
    protected static function getVarietyTypeFilter(): SelectFilter
    {
        return SelectFilter::make('variety_type')
            ->label('Product Type')
            ->options([
                'single' => 'Single Variety',
                'mix' => 'Product Mix',
                'none' => 'No Variety Assigned',
            ])
            ->query(function (Builder $query, array $data): Builder {
                return match($data['value']) {
                    'single' => $query->whereNotNull('master_seed_catalog_id'),
                    'mix' => $query->whereNotNull('product_mix_id'),
                    'none' => $query->whereNull('master_seed_catalog_id')->whereNull('product_mix_id'),
                    default => $query,
                };
            });
    }

    /**
     * Get product cloning action for agricultural variety creation.
     *
     * Provides sophisticated product duplication functionality essential for
     * agricultural product management where similar varieties often share common
     * characteristics. Clones product information, price variations, and photos
     * while creating new unique identifiers and maintaining agricultural context.
     *
     * @return Action Product cloning with agricultural context preservation
     * @agricultural_workflow Enables rapid creation of similar agricultural varieties
     * @business_efficiency Duplicates pricing and packaging configurations
     * @data_integrity Creates unique names and SKUs while preserving agricultural data
     * @user_experience Confirmation modal with agricultural context explanation
     */
    protected static function getCloneAction(): Action
    {
        return Action::make('clone')
            ->label('Clone')
            ->icon('heroicon-o-document-duplicate')
            ->tooltip('Clone this product')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Clone Product')
            ->modalDescription('This will create a copy of the product with all its price variations and photos. Inventory will not be copied.')
            ->modalSubmitActionLabel('Clone Product')
            ->action(function (Product $record) {
                try {
                    $newProduct = app(CloneProductAction::class)->execute($record);
                    
                    Notification::make()
                        ->title('Product Cloned Successfully')
                        ->body("Created: {$newProduct->name}")
                        ->success()
                        ->send();
                        
                    // Redirect to the edit page of the new product
                    return redirect()->to(ProductResource::getUrl('edit', ['record' => $newProduct]));
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Clone Failed')
                        ->body('Failed to clone product: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get deletion action with agricultural business rule validation.
     *
     * Implements sophisticated deletion validation that prevents removal of
     * agricultural products with active crop plans, ongoing orders, or other
     * agricultural workflow dependencies. Provides clear feedback about why
     * deletion is blocked to support agricultural decision-making.
     *
     * @return DeleteAction Validated deletion with agricultural workflow protection
     * @agricultural_validation Prevents deletion of products with active crops
     * @business_protection Maintains agricultural workflow integrity
     * @user_feedback Clear explanation of deletion constraints and requirements
     * @workflow_safety Protects ongoing agricultural operations from disruption
     */
    protected static function getDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip('Delete record')
            ->before(function (Product $record) {
                $deleteCheck = app(ValidateProductDeletionAction::class)->execute($record);
                
                if (!$deleteCheck['canDelete']) {
                    Notification::make()
                        ->title('Cannot Delete Product')
                        ->body("Product '{$record->name}' cannot be deleted:\n" . implode("\n", $deleteCheck['errors']))
                        ->danger()
                        ->send();
                    
                    // Cancel the deletion
                    return false;
                }
            });
    }

    /**
     * Get bulk deletion action with comprehensive agricultural validation.
     *
     * Extends single-product deletion validation to bulk operations, ensuring
     * that no agricultural products with active crops or workflow dependencies
     * are accidentally removed during bulk management operations. Provides
     * detailed feedback about which products cannot be deleted and why.
     *
     * @return DeleteBulkAction Bulk deletion with agricultural workflow protection
     * @agricultural_safety Validates each product for active crop dependencies
     * @business_protection Prevents disruption of ongoing agricultural operations
     * @user_guidance Clear feedback about deletion constraints across multiple products
     * @workflow_integrity Maintains agricultural business process continuity
     */
    protected static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->before(function ($records) {
                // Check each record for inventory
                foreach ($records as $record) {
                    $deleteCheck = app(ValidateProductDeletionAction::class)->execute($record);
                    
                    if (!$deleteCheck['canDelete']) {
                        Notification::make()
                            ->title('Cannot Delete Products')
                            ->body("Product '{$record->name}' cannot be deleted:\n" . implode("\n", $deleteCheck['errors']) . "\n\nPlease resolve issues for all selected products first.")
                            ->danger()
                            ->send();
                        
                        // Cancel the deletion
                        return false;
                    }
                }
            });
    }

    /**
     * Get bulk activation action for seasonal agricultural product management.
     *
     * Provides efficient bulk activation of agricultural products, typically used
     * for seasonal availability changes when varieties come into planting season
     * or become available for customer orders. Integrates with agricultural
     * workflow systems for inventory and crop planning updates.
     *
     * @return BulkAction Bulk product activation for seasonal agricultural management
     * @agricultural_workflow Seasonal activation for planting season availability
     * @business_efficiency Mass status changes for agricultural product catalogs
     * @seasonal_management Supports agricultural business seasonal operations
     */
    protected static function getActivateBulkAction(): BulkAction
    {
        return BulkAction::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->action(function ($records) {
                app(BulkUpdateProductStatusAction::class)->activate($records);
            })
            ->requiresConfirmation()
            ->color('success');
    }

    /**
     * Get bulk deactivation action for agricultural product lifecycle management.
     *
     * Enables efficient bulk deactivation of agricultural products, commonly used
     * for end-of-season availability changes or when varieties are discontinued.
     * Maintains agricultural workflow integrity while updating product availability
     * for customer-facing systems and internal planning processes.
     *
     * @return BulkAction Bulk product deactivation for agricultural lifecycle management
     * @agricultural_lifecycle End-of-season or discontinuation workflow support
     * @business_workflow Mass deactivation for agricultural product management
     * @inventory_integration Updates availability for order processing systems
     */
    protected static function getDeactivateBulkAction(): BulkAction
    {
        return BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->action(function ($records) {
                app(BulkUpdateProductStatusAction::class)->deactivate($records);
            })
            ->requiresConfirmation()
            ->color('danger');
    }

    /**
     * Get bulk store visibility action for customer-facing agricultural catalogs.
     *
     * Provides efficient management of product visibility in customer-facing
     * agricultural product catalogs. Enables bulk updates when varieties become
     * available for customer ordering or when seasonal products are ready for
     * market presentation.
     *
     * @return BulkAction Bulk store visibility for customer-facing agricultural catalogs
     * @customer_interface Controls visibility in agricultural product storefronts
     * @business_workflow Seasonal availability management for customer systems
     * @agricultural_marketing Product visibility for agricultural market presentation
     */
    protected static function getShowInStoreBulkAction(): BulkAction
    {
        return BulkAction::make('show_in_store')
            ->label('Show in Store')
            ->icon('heroicon-o-eye')
            ->action(function ($records) {
                app(BulkUpdateProductStatusAction::class)->showInStore($records);
            });
    }

    /**
     * Get bulk store hiding action for agricultural product catalog management.
     *
     * Enables efficient removal of products from customer-facing agricultural
     * catalogs while maintaining internal product records. Useful for seasonal
     * transitions, inventory shortages, or when agricultural products are
     * temporarily unavailable for customer ordering.
     *
     * @return BulkAction Bulk store hiding for agricultural catalog management
     * @customer_experience Removes unavailable products from customer interfaces
     * @inventory_management Hides products during stock shortages or seasonal gaps
     * @agricultural_workflow Supports seasonal availability and inventory transitions
     */
    protected static function getHideFromStoreBulkAction(): BulkAction
    {
        return BulkAction::make('hide_from_store')
            ->label('Hide from Store')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->action(function ($records) {
                app(BulkUpdateProductStatusAction::class)->hideFromStore($records);
            });
    }

    /**
     * Configure query optimizations for agricultural product table display.
     *
     * Implements eager loading strategies to prevent N+1 queries when displaying
     * agricultural product information including categories, variety details,
     * pricing variations, and packaging information. Essential for performance
     * with large agricultural product catalogs.
     *
     * @param Builder $query Base Eloquent query builder
     * @return Builder Optimized query with agricultural relationship eager loading
     * @performance_optimization Prevents N+1 queries for related agricultural data
     * @relationship_loading Category, variety, mix, recipe, and pricing information
     * @agricultural_efficiency Optimized display of complex agricultural product relationships
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'category',
            'masterSeedCatalog.cultivar',
            'productMix',
            'recipe',
            'priceVariations.packagingType'
        ]);
    }
}