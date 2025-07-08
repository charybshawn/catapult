<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use App\Http\Livewire\ItemPriceCalculator;
use Livewire\Livewire;
use App\Models\Crop;
use App\Models\Order;
use App\Models\Payment;
use App\Observers\CropObserver;
use App\Observers\OrderObserver;
use App\Observers\OrderStatusObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use App\Services\GitService;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register CropTaskManagementService as a singleton
        $this->app->singleton(\App\Services\CropTaskManagementService::class);
        
        // Register the unified InventoryManagementService as a singleton with dependencies
        $this->app->singleton(\App\Services\InventoryManagementService::class, function ($app) {
            return new \App\Services\InventoryManagementService(
                $app->make('config')
            );
        });
        
        // Register CropValidationService as a singleton with dependencies
        $this->app->singleton(\App\Services\CropValidationService::class, function ($app) {
            return new \App\Services\CropValidationService(
                $app->make(\App\Services\CropTaskManagementService::class),
                $app->make(\App\Services\InventoryManagementService::class)
            );
        });
        
        // Register RecipeService as a singleton with dependencies
        $this->app->singleton(\App\Services\RecipeService::class, function ($app) {
            return new \App\Services\RecipeService(
                $app->make(\App\Services\InventoryManagementService::class)
            );
        });
        
        // Register CropTimeCalculator as a singleton
        $this->app->singleton(\App\Services\CropTimeCalculator::class);
        
        // Register ConsumableCalculatorService as a singleton
        $this->app->singleton(\App\Services\ConsumableCalculatorService::class);
        
        // Register StatusTransitionService as a singleton
        $this->app->singleton(\App\Services\StatusTransitionService::class);
        
        // Register legacy service aliases for backward compatibility
        $this->app->bind(\App\Services\CropTaskService::class, function ($app) {
            return $app->make(\App\Services\CropTaskManagementService::class);
        });
        
        $this->app->bind(\App\Services\CropLifecycleService::class, function ($app) {
            return $app->make(\App\Services\CropTaskManagementService::class);
        });

        $this->app->bind(\App\Services\InventoryService::class, function ($app) {
            return $app->make(\App\Services\InventoryManagementService::class);
        });

        $this->app->bind(\App\Services\LotInventoryService::class, function ($app) {
            return $app->make(\App\Services\InventoryManagementService::class);
        });

        $this->app->bind(\App\Services\LotDepletionService::class, function ($app) {
            return $app->make(\App\Services\InventoryManagementService::class);
        });

        $this->app->bind(\App\Services\CropInventoryService::class, function ($app) {
            return $app->make(\App\Services\InventoryManagementService::class);
        });
        
        $this->app->singleton(\App\Services\RecipeVarietyService::class, function ($app) {
            return new \App\Services\RecipeVarietyService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS for all environments
        \Illuminate\Support\Facades\URL::forceScheme('https');
        
        // Register Livewire components
        Livewire::component('item-price-calculator', ItemPriceCalculator::class);
        
        // Register model observers
        Crop::observe(CropObserver::class);
        Order::observe(OrderObserver::class);
        Order::observe(OrderStatusObserver::class);
        \App\Models\OrderItem::observe(\App\Observers\OrderItemObserver::class);
        Payment::observe(PaymentObserver::class);

        
        
        // Prevent migrations in production unless explicitly allowed
        Model::preventLazyLoading(! app()->isProduction());

        // Add git branch indicator to Filament admin panel topbar
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn (): string => $this->renderGitBranchIndicator()
        );

        if ($this->app->environment('production') && !$this->app->runningInConsole()) {
            // Prevent migrations in production unless explicitly overridden
            if (app()->environment('production') &&
                !config('app.allow_migrations_in_production', false)) {
                // Disable migrations in production 
                $this->app->bind('migrator', function ($app) {
                    return new class($app['migration.repository'], $app['db'], $app['files'], $app['events']) extends \Illuminate\Database\Migrations\Migrator {
                        public function run($paths = [], array $options = [])
                        {
                            throw new \RuntimeException(
                                'Migrations are disabled in production for safety. ' .
                                'To run migrations in production, set ALLOW_MIGRATIONS_IN_PRODUCTION=true in your .env file.'
                            );
                        }
                    };
                });
            }
        }
    }

    /**
     * Render the git branch indicator for Filament
     */
    private function renderGitBranchIndicator(): string
    {
        if (!GitService::isGitRepository()) {
            return '';
        }

        $branch = GitService::getCurrentBranch();
        
        return Blade::render('
            <div class="flex items-center mr-4">
                <div class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-md text-xs font-mono text-gray-600 dark:text-gray-300">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414L2.586 7a2 2 0 010-2.828l3.707-3.707a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ $branch }}
                </div>
            </div>
        ', ['branch' => $branch]);
    }
}
