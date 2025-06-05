<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use App\Http\Livewire\ItemPriceCalculator;
use Livewire\Livewire;
use App\Models\Crop;
use App\Observers\CropObserver;
use Illuminate\Support\Facades\DB;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('item-price-calculator', ItemPriceCalculator::class);
        Crop::observe(CropObserver::class);

        // Add debug logging for isContained method
        $this->debugIsContainedMethod();
        
        // Optimize Filament layout for wide screens
        $this->optimizeFilamentLayout();
        
        // Prevent migrations in production unless explicitly allowed
        if ($this->app->environment('production') && !$this->app->runningInConsole()) {
            // Disable database statements in production to prevent changes
            DB::preventLazyLoading(!$this->app->environment('production'));
            
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
     * Set up debugging for the isContained method
     */
    private function debugIsContainedMethod(): void
    {
        // Only run in local environment
        if (! app()->environment('local')) {
            return;
        }

        // Set up error handler to track "Call to a member function isContained() on null"
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (str_contains($errstr, 'isContained() on null')) {
                // Log detailed information when this specific error occurs
                \Illuminate\Support\Facades\Log::error('isContained() called on null', [
                    'file' => $errfile,
                    'line' => $errline,
                    'backtrace' => $this->getFormattedBacktrace(),
                    'time' => now()->toDateTimeString(),
                ]);
                
                // Create a debug dump of the error context
                \App\Services\DebugService::writeToFile(
                    date('Y-m-d_H-i-s') . '_isContained_error.json',
                    [
                        'error' => $errstr,
                        'file' => $errfile,
                        'line' => $errline,
                        'backtrace' => $this->getFormattedBacktrace(),
                        'request_url' => request()->fullUrl(),
                        'session_id' => session()->getId(),
                    ]
                );
            }
            
            // Let the default handler process the error
            return false;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);
    }
    
    /**
     * Get a formatted backtrace for debugging
     */
    private function getFormattedBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $formattedTrace = [];
        
        foreach ($trace as $item) {
            $formattedTrace[] = [
                'file' => $item['file'] ?? 'Unknown',
                'line' => $item['line'] ?? 0,
                'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? ''),
            ];
        }
        
        return $formattedTrace;
    }
    
    /**
     * Optimize Filament layout for wide screens
     */
    private function optimizeFilamentLayout(): void
    {
        // Add custom CSS for wide screen optimization to the head
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '
                <style>
                    /* BALANCED: Better width utilization without overflow */
                    @media (min-width: 1440px) {
                        /* Set reasonable max-widths for different screen sizes */
                        .fi-main, .fi-page, .fi-page-content {
                            max-width: calc(100vw - 18rem) !important; /* Account for sidebar + some margin */
                            width: 100% !important;
                            margin: 0 !important;
                            padding-left: 1rem !important;
                            padding-right: 1rem !important;
                        }
                        
                        /* Content containers should use available width with padding */
                        .fi-simple-page, .fi-resource-page-content,
                        .fi-main-ctn, .container {
                            max-width: calc(100vw - 20rem) !important; /* Sidebar + margins */
                            width: 100% !important;
                            margin: 0 !important;
                            padding-left: 1rem !important;
                            padding-right: 1rem !important;
                        }
                        
                        /* Override restrictive Tailwind classes with reasonable limits */
                        .max-w-xs, .max-w-sm, .max-w-md, .max-w-lg, .max-w-xl, 
                        .max-w-2xl, .max-w-3xl, .max-w-4xl {
                            max-width: calc(100vw - 20rem) !important;
                        }
                        
                        /* Remove centering but keep content within viewport */
                        .mx-auto {
                            margin-left: 1rem !important;
                            margin-right: 1rem !important;
                            max-width: calc(100vw - 20rem) !important;
                        }
                        
                        /* Tables should use available space without overflow */
                        .fi-ta-content, .fi-ta-table {
                            width: 100% !important;
                            max-width: calc(100vw - 22rem) !important;
                            overflow-x: auto !important;
                        }
                        
                        /* Forms should expand but stay within bounds */
                        .fi-fo, .fi-form, form {
                            width: 100% !important;
                            max-width: calc(100vw - 20rem) !important;
                        }
                        
                        /* Cards and sections with reasonable bounds */
                        .fi-section, .fi-card, .fi-widget {
                            width: 100% !important;
                            max-width: calc(100vw - 20rem) !important;
                        }
                    }
                    
                    /* Larger screens get more space */
                    @media (min-width: 1920px) {
                        .fi-main, .fi-page, .fi-page-content,
                        .fi-simple-page, .fi-resource-page-content {
                            max-width: calc(100vw - 22rem) !important; /* Wider sidebar accounted for */
                        }
                        
                        .fi-sidebar {
                            width: 20rem !important;
                        }
                        
                        .fi-main {
                            margin-left: 20rem !important;
                        }
                    }
                    
                    /* Ultra-wide screens */
                    @media (min-width: 2560px) {
                        .fi-main, .fi-page, .fi-page-content,
                        .fi-simple-page, .fi-resource-page-content {
                            max-width: calc(100vw - 24rem) !important; /* Even wider sidebar */
                        }
                        
                        .fi-sidebar {
                            width: 22rem !important;
                        }
                        
                        .fi-main {
                            margin-left: 22rem !important;
                        }
                    }
                </style>
            '
        );
    }
}
