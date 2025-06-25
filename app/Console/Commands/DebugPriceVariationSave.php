<?php

namespace App\Console\Commands;

use App\Models\PriceVariation;
use App\Models\PackagingType;
use Illuminate\Console\Command;

class DebugPriceVariationSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:price-variation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug price variation saving with packaging types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing PriceVariation saving with packaging types...');
        
        // Get a packaging type
        $packagingType = PackagingType::first();
        if (!$packagingType) {
            $this->error('No packaging types found!');
            return 1;
        }
        
        $this->info("Using packaging type: {$packagingType->name} (ID: {$packagingType->id})");
        
        // Test 1: Create a global template
        $this->info("\n--- Test 1: Creating global template ---");
        $template = new PriceVariation([
            'name' => 'Debug Template',
            'price' => 15.99,
            'packaging_type_id' => $packagingType->id,
            'is_global' => true,
            'is_active' => true,
        ]);
        
        $this->info("Before save:");
        $this->info("- packaging_type_id: {$template->packaging_type_id}");
        $this->info("- is_global: " . ($template->is_global ? 'true' : 'false'));
        
        $template->save();
        $template->refresh();
        
        $this->info("After save:");
        $this->info("- ID: {$template->id}");
        $this->info("- packaging_type_id: {$template->packaging_type_id}");
        $this->info("- name: {$template->name}");
        
        // Check if packaging relationship works
        if ($template->packagingType) {
            $this->info("- packaging relationship: {$template->packagingType->name}");
        } else {
            $this->warn("- packaging relationship: NULL");
        }
        
        // Test 2: Update the template
        $this->info("\n--- Test 2: Updating template ---");
        $newPackaging = PackagingType::where('id', '!=', $packagingType->id)->first();
        if ($newPackaging) {
            $this->info("Changing packaging to: {$newPackaging->name} (ID: {$newPackaging->id})");
            $template->packaging_type_id = $newPackaging->id;
            $template->save();
            $template->refresh();
            
            $this->info("After update:");
            $this->info("- packaging_type_id: {$template->packaging_type_id}");
            if ($template->packagingType) {
                $this->info("- packaging relationship: {$template->packagingType->name}");
            } else {
                $this->warn("- packaging relationship: NULL");
            }
        }
        
        // Cleanup
        $template->delete();
        $this->info("\nTest template deleted.");
        
        return 0;
    }
}
