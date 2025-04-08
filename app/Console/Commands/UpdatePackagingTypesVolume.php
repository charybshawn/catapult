<?php

namespace App\Console\Commands;

use App\Models\PackagingType;
use Illuminate\Console\Command;

class UpdatePackagingTypesVolume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-packaging-types-volume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update packaging types to include volume values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $packagingTypes = PackagingType::all();
        $count = 0;
        
        $this->info('Updating packaging types with volume data...');
        $this->newLine();
        
        $bar = $this->output->createProgressBar(count($packagingTypes));
        $bar->start();
        
        foreach ($packagingTypes as $packagingType) {
            // Default to 24oz for clamshells as specified by the user
            if (stripos($packagingType->name, 'clamshell') !== false) {
                $packagingType->capacity_volume = 24;
                $packagingType->volume_unit = 'oz';
            } 
            // For other types, default to a reasonable value
            else {
                // Set a default volume based on the name
                if (stripos($packagingType->name, 'small') !== false) {
                    $packagingType->capacity_volume = 8;
                } elseif (stripos($packagingType->name, 'medium') !== false) {
                    $packagingType->capacity_volume = 16;
                } elseif (stripos($packagingType->name, 'large') !== false) {
                    $packagingType->capacity_volume = 32;
                } else {
                    $packagingType->capacity_volume = 16; // Default
                }
                
                $packagingType->volume_unit = 'oz';
            }
            
            $packagingType->save();
            $count++;
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Updated $count packaging types with volume data.");
    }
}
