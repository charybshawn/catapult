<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use App\Http\Livewire\ItemPriceCalculator;
use Livewire\Livewire;
use App\Models\Crop;
use App\Observers\CropObserver;

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
}
