<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Category;
use App\Models\PriceVariation;
use App\Models\Setting;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cache active categories for 30 minutes
        $this->cacheActiveCategories();
        
        // Cache active products for 15 minutes
        $this->cacheActiveProducts();
        
        // Cache global price variations for 60 minutes
        $this->cacheGlobalPriceVariations();
        
        // Cache application settings for 24 hours
        $this->cacheSettings();
        
        // Set up cache invalidation listeners for models
        $this->setupCacheInvalidation();
    }
    
    /**
     * Cache active categories
     */
    private function cacheActiveCategories(): void
    {
        Category::saved(function () {
            Cache::forget('active_categories');
        });
        
        Category::deleted(function () {
            Cache::forget('active_categories');
        });
    }
    
    /**
     * Cache active products
     */
    private function cacheActiveProducts(): void
    {
        Product::saved(function () {
            Cache::forget('active_products');
            Cache::forget('store_visible_products');
        });
        
        Product::deleted(function () {
            Cache::forget('active_products');
            Cache::forget('store_visible_products');
        });
    }
    
    /**
     * Cache global price variations
     */
    private function cacheGlobalPriceVariations(): void
    {
        PriceVariation::saved(function ($variation) {
            if ($variation->is_global) {
                Cache::forget('global_price_variations');
            }
        });
        
        PriceVariation::deleted(function ($variation) {
            if ($variation->is_global) {
                Cache::forget('global_price_variations');
            }
        });
    }
    
    /**
     * Cache application settings
     */
    private function cacheSettings(): void
    {
        Setting::saved(function () {
            Cache::forget('app_settings');
        });
        
        Setting::deleted(function () {
            Cache::forget('app_settings');
        });
    }
    
    /**
     * Set up cache invalidation listeners
     */
    private function setupCacheInvalidation(): void
    {
        // When a model is updated, invalidate related caches
        
        // Example: When a product is updated, invalidate related category caches
        Product::updated(function ($product) {
            if ($product->isDirty('category_id')) {
                Cache::forget('category_products_' . $product->getOriginal('category_id'));
                Cache::forget('category_products_' . $product->category_id);
            }
        });
    }
}
