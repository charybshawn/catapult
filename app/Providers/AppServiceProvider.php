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
                    /* Additional layout optimizations for very wide screens */
                    @media (min-width: 2560px) {
                        .fi-sidebar {
                            width: 20rem !important; /* Even wider sidebar on ultra-wide screens */
                        }
                        
                        .fi-main {
                            margin-left: 20rem !important;
                        }
                        
                        .fi-page-content {
                            max-width: 100rem !important; /* Allow very wide content on huge screens */
                        }
                    }
                    
                    /* Optimize table responsiveness */
                    @media (min-width: 1440px) {
                        .fi-ta-content {
                            overflow-x: visible !important;
                        }
                        
                        /* Better spacing for action buttons */
                        .fi-ta-actions {
                            white-space: nowrap;
                        }
                        
                        /* Improve form section layouts */
                        .fi-section-content-ctn {
                            padding-left: 1.5rem !important;
                            padding-right: 1.5rem !important;
                        }
                    }
                </style>
            '
        );
    }
}
