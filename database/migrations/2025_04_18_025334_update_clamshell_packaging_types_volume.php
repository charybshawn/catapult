<?php

use App\Models\PackagingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update clamshell packaging types with proper volume values
        $clamshells = PackagingType::where('name', 'like', '%Clamshell%')->get();
        
        foreach ($clamshells as $clamshell) {
            // If volume is 0, let's set a default value
            // The value depends on what makes sense for your business
            if ($clamshell->capacity_volume == 0) {
                // Parse any numeric values from the name if possible
                preg_match('/(\d+(\.\d+)?)/', $clamshell->name, $matches);
                
                if (!empty($matches[1])) {
                    // If a numeric value exists in the name, use it as the volume
                    $clamshell->capacity_volume = (float) $matches[1];
                } else {
                    // Otherwise set a default based on the packaging type
                    // These are sample values - adjust based on your actual packaging
                    $clamshell->capacity_volume = 8.0; // Default to 8 oz for clamshells
                }
                
                // Ensure volume unit is set
                if (empty($clamshell->volume_unit)) {
                    $clamshell->volume_unit = 'oz';
                }
                
                $clamshell->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to revert as we're fixing data consistency
    }
};
