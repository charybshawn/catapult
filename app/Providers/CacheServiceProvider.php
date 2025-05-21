<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

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
     * Handle model changes and clear appropriate caches
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
