<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ToggleDebugMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:toggle {--enable} {--disable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle debug mode on/off for the admin interface';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentValue = \App\Models\Setting::getValue('debug_mode_enabled', false);
        
        if ($this->option('enable')) {
            \App\Models\Setting::setValue('debug_mode_enabled', true, 'Enable debug information display in admin interface', 'development');
            $this->info('Debug mode enabled.');
        } elseif ($this->option('disable')) {
            \App\Models\Setting::setValue('debug_mode_enabled', false, 'Enable debug information display in admin interface', 'development');
            $this->info('Debug mode disabled.');
        } else {
            // Toggle current value
            $newValue = !$currentValue;
            \App\Models\Setting::setValue('debug_mode_enabled', $newValue, 'Enable debug information display in admin interface', 'development');
            $this->info('Debug mode ' . ($newValue ? 'enabled' : 'disabled') . '.');
        }
        
        $newValue = \App\Models\Setting::getValue('debug_mode_enabled', false);
        $this->line('Current debug mode status: ' . ($newValue ? 'enabled' : 'disabled'));
        
        return 0;
    }
}
