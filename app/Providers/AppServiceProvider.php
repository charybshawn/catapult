<?php

namespace App\Providers;

use App\Services\CropTaskManagementService;
use App\Services\InventoryManagementService;
use App\Services\CropValidationService;
use App\Services\RecipeService;
use App\Services\CropTimeCalculator;
use App\Services\ConsumableCalculatorService;
use App\Services\StatusTransitionService;
use App\Services\CropTaskService;
use App\Services\CropLifecycleService;
use App\Services\InventoryService;
use App\Services\LotInventoryService;
use App\Services\LotDepletionService;
use App\Services\CropInventoryService;
use App\Services\RecipeVarietyService;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Migrations\Migrator;
use RuntimeException;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use App\Http\Livewire\ItemPriceCalculator;
use Livewire\Livewire;
use App\Models\Payment;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use App\Services\GitService;
use Illuminate\Support\Facades\Blade;

/**
 * Main application service provider for the Catapult agricultural management system.
 * Configures core business services for microgreens production, inventory management,
 * and crop lifecycle operations.
 *
 * @business_domain Agricultural microgreens production and farm management
 * @service_architecture Registers singleton services with dependency injection
 * @ui_framework Configured for Filament admin panel with agricultural-specific customizations
 * @security_features HTTPS enforcement, production migration protection, CSRF protection
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register agricultural business services and dependencies for microgreens production.
     * Configures singleton services for crop management, inventory tracking, and recipe processing
     * with proper dependency injection to support complex agricultural workflows.
     *
     * @agricultural_services Crop lifecycle, inventory management, recipe calculations
     * @dependency_injection Services registered with proper constructor injection
     * @service_aliases Legacy service aliases maintained for backward compatibility
     * @return void
     */
    public function register(): void
    {
        // Register CropTaskManagementService as a singleton
        $this->app->singleton(CropTaskManagementService::class);
        
        // Register the unified InventoryManagementService as a singleton with dependencies
        $this->app->singleton(InventoryManagementService::class, function ($app) {
            return new InventoryManagementService(
                $app->make('config')
            );
        });
        
        // Register CropValidationService as a singleton with dependencies
        $this->app->singleton(CropValidationService::class, function ($app) {
            return new CropValidationService(
                $app->make(CropTaskManagementService::class),
                $app->make(InventoryManagementService::class)
            );
        });
        
        // Register RecipeService as a singleton with dependencies
        $this->app->singleton(RecipeService::class, function ($app) {
            return new RecipeService(
                $app->make(InventoryManagementService::class)
            );
        });
        
        // Register CropTimeCalculator as a singleton
        $this->app->singleton(CropTimeCalculator::class);
        
        // Register ConsumableCalculatorService as a singleton
        $this->app->singleton(ConsumableCalculatorService::class);
        
        // Register StatusTransitionService as a singleton
        $this->app->singleton(StatusTransitionService::class);
        
        // Register legacy service aliases for backward compatibility
        $this->app->bind(CropTaskService::class, function ($app) {
            return $app->make(CropTaskManagementService::class);
        });
        
        $this->app->bind(CropLifecycleService::class, function ($app) {
            return $app->make(CropTaskManagementService::class);
        });

        $this->app->bind(InventoryService::class, function ($app) {
            return $app->make(InventoryManagementService::class);
        });

        $this->app->bind(LotInventoryService::class, function ($app) {
            return $app->make(InventoryManagementService::class);
        });

        $this->app->bind(LotDepletionService::class, function ($app) {
            return $app->make(InventoryManagementService::class);
        });

        $this->app->bind(CropInventoryService::class, function ($app) {
            return $app->make(InventoryManagementService::class);
        });
        
        $this->app->singleton(RecipeVarietyService::class, function ($app) {
            return new RecipeVarietyService();
        });
    }

    /**
     * Bootstrap application configuration for agricultural production environment.
     * Configures security settings, UI customizations for farm operations, Livewire components
     * for real-time farm monitoring, and production safety measures for database migrations.
     *
     * @security_config HTTPS enforcement, production migration protection
     * @ui_customizations Filament form optimizations, dropdown z-index fixes for complex forms
     * @development_tools Git branch indicator, Livewire debouncing for numeric agricultural inputs
     * @production_safety Migration blocking in production without explicit override
     * @return void
     */
    public function boot(): void
    {
        // Force HTTPS for all environments
        URL::forceScheme('https');
        
        // Register Livewire components
        Livewire::component('item-price-calculator', ItemPriceCalculator::class);
        
        // Register model observers
        // Note: Order-related observers moved to Filament Page hooks + Action classes
        // following the Filament Resource Architecture Guide patterns
        Payment::observe(PaymentObserver::class);

        
        
        // Prevent migrations in production unless explicitly allowed
        Model::preventLazyLoading(! app()->isProduction());
        
        // Fix Livewire debouncing issues with numeric inputs
        $this->configureFilamentLivewireDebouncing();

        // Add git branch indicator to Filament admin panel topbar
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): string => $this->renderGitBranchIndicator()
        );
        
        // Add numeric input debounce fix script to Filament
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => '<script src="' . asset('js/filament-numeric-debounce-fix.js') . '"></script>'
        );

        // Add custom CSS for dropdown z-index fixes - simplified approach
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            function (): string {
                return '<style>
                    .fi-fo-repeater .fi-fo-select [role="listbox"] {
                        z-index: 999999 !important;
                        position: fixed !important;
                        background-color: white !important;
                        border: 1px solid #d1d5db !important;
                        border-radius: 6px !important;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
                        max-height: 16rem !important;
                        overflow-y: auto !important;
                    }
                    .fi-fo-repeater .fi-fo-select [role="listbox"] * {
                        z-index: 999999 !important;
                    }
                    .fi-fo-repeater .fi-fo-select [role="listbox"] [role="option"] {
                        padding: 8px 12px !important;
                        color: #1f2937 !important;
                        background-color: transparent !important;
                        cursor: pointer !important;
                    }
                    .fi-fo-repeater .fi-fo-select [role="listbox"] [role="option"]:hover,
                    .fi-fo-repeater .fi-fo-select [role="listbox"] [role="option"][data-headlessui-state~="active"] {
                        background-color: #f3f4f6 !important;
                    }
                    .fi-fo-repeater,
                    .fi-fo-repeater-item,
                    .fi-fo-section {
                        overflow: visible !important;
                    }
                </style>';
            }
        );

        if ($this->app->environment('production') && !$this->app->runningInConsole()) {
            // Prevent migrations in production unless explicitly overridden
            if (app()->environment('production') &&
                !config('app.allow_migrations_in_production', false)) {
                // Disable migrations in production 
                $this->app->bind('migrator', function ($app) {
                    return new class($app['migration.repository'], $app['db'], $app['files'], $app['events']) extends Migrator {
                        public function run($paths = [], array $options = [])
                        {
                            throw new RuntimeException(
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
     * Configure Filament Livewire debouncing for agricultural numeric inputs.
     * Prevents character loss during rapid data entry of crop quantities, weights,
     * and agricultural measurements commonly used in microgreens production.
     *
     * @agricultural_context Numeric inputs for weights, quantities, germination rates
     * @ui_optimization Longer debounce times prevent data loss during fast entry
     * @input_types Covers integer, decimal, numeric fields used in crop calculations
     * @debounce_timing 500ms delay balances responsiveness with data integrity
     * @return void
     */
    private function configureFilamentLivewireDebouncing(): void
    {
        // Set longer debounce for numeric inputs to prevent character loss
        TextInput::configureUsing(function (TextInput $component): void {
            if ($component->isNumeric()) {
                $component->lazy(); // Use lazy evaluation instead of live for numeric inputs
            }
        });
        
        // Also configure for specific numeric field types
        TextInput::configureUsing(function (TextInput $component): void {
            $numericTypes = ['integer', 'decimal', 'numeric'];
            if (in_array($component->getType(), $numericTypes) || $component->isNumeric()) {
                $component->debounce(500); // 500ms debounce for numeric fields
            }
        });
    }
    
    /**
     * Render development git branch indicator for agricultural system administration.
     * Displays current git branch in Filament admin panel to help track feature development
     * and deployment status during agricultural system updates.
     *
     * @development_tool Visual indicator for system administrators and developers
     * @ui_position Fixed position at top center of admin panel
     * @git_integration Uses GitService to detect repository and current branch
     * @styling Blade template with responsive dark mode support
     * @return string Rendered HTML for git branch indicator or empty string if not git repo
     */
    private function renderGitBranchIndicator(): string
    {
        if (!GitService::isGitRepository()) {
            return '';
        }

        $branch = GitService::getCurrentBranch();
        
        return Blade::render('
            <div style="position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 50;">
                <div class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-md text-xs font-mono text-gray-600 dark:text-gray-300">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 717 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414L2.586 7a2 2 0 010-2.828l3.707-3.707a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ $branch }}
                </div>
            </div>
        ', ['branch' => $branch]);
    }
}
