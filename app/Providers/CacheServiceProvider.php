<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Agricultural Cache Management Service Provider
 * 
 * Provides intelligent cache invalidation for agricultural business data, ensuring
 * dashboard performance while maintaining data consistency. This provider uses
 * Eloquent event listeners to automatically invalidate cached agricultural data
 * when underlying models change, preventing stale data in production planning.
 * 
 * Cache Strategy:
 * - Event-driven invalidation: Automatic cache clearing on model changes
 * - Migration-safe listeners: Generic event handling prevents migration failures
 * - Model-specific logic: Targeted cache invalidation for agricultural entities
 * - Performance optimization: Strategic caching for frequently accessed agricultural data
 * 
 * Agricultural Data Caching:
 * - Categories: Product categorization for agricultural variety organization
 * - Products: Core agricultural products and variety specifications
 * - Price Variations: Package sizing and pricing for customer order processing
 * - Settings: System configuration affecting agricultural business operations
 * 
 * @business_domain Agricultural data caching and performance optimization
 * @performance Dashboard and order processing speed optimization
 * @data_consistency Automatic cache invalidation for real-time agricultural data
 * 
 * @see \App\Models\Category For agricultural product categorization
 * @see \App\Models\Product For core agricultural variety and mix definitions
 * @see \App\Models\PriceVariation For package pricing and sizing specifications
 * @see \App\Models\Setting For agricultural system configuration
 */
class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register cache management services
     * 
     * No services are registered in this provider as cache invalidation
     * is handled through event listeners configured in the boot method.
     * 
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap agricultural cache management event listeners
     * 
     * Configures automatic cache invalidation for agricultural business data
     * using Eloquent model events. Uses generic event listeners to prevent
     * migration failures when specific models might not be available.
     * 
     * @return void
     * @business_context Ensures agricultural dashboards show real-time data
     */
    public function boot(): void
    {
        // Use generic event listeners for models
        // This prevents errors during migrations when models might not be loaded yet
        
        // Listen for any Eloquent model events
        Event::listen('eloquent.saved: *', function ($event, $models) {
            if (isset($models[0])) {
                $this->handleModelChanged($models[0]);
            }
        });
        
        Event::listen('eloquent.deleted: *', function ($event, $models) {
            if (isset($models[0])) {
                $this->handleModelChanged($models[0]);
            }
        });
    }
    
    /**
     * Handle agricultural model changes and clear appropriate cached data
     * 
     * Processes Eloquent model events to invalidate specific cached agricultural
     * data when underlying models change. This ensures dashboard performance
     * while maintaining data consistency for production planning.
     * 
     * Cache Invalidation Strategy:
     * - Categories: Clear category caches when agricultural categorization changes
     * - Products: Clear product and category-specific caches for variety management
     * - Price Variations: Clear global pricing caches for order processing
     * - Settings: Clear system configuration caches for agricultural operations
     * 
     * @param mixed $model The Eloquent model that triggered the event
     * @return void
     * 
     * @business_context Maintains real-time agricultural data accuracy
     * @performance Targeted invalidation prevents unnecessary cache clearing
     */
    private function handleModelChanged($model): void
    {
        if (!$model) return;
        
        $class = get_class($model);
        
        // Handle different model types
        switch ($class) {
            case 'App\Models\Category':
                Cache::forget('active_categories');
                break;
                
            case 'App\Models\Product':
                Cache::forget('active_products');
                Cache::forget('store_visible_products');
                
                // Clear category cache if category changed
                if ($model->isDirty('category_id')) {
                    Cache::forget('category_products_' . $model->getOriginal('category_id'));
                    Cache::forget('category_products_' . $model->category_id);
                }
                break;
                
            case 'App\Models\PriceVariation':
                if ($model->is_global ?? false) {
                    Cache::forget('global_price_variations');
                }
                break;
                
            case 'App\Models\Setting':
                Cache::forget('app_settings');
                break;
        }
    }
}
