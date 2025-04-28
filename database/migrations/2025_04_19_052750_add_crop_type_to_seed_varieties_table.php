<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SeedVariety;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seed_varieties', function (Blueprint $table) {
            $table->string('crop_type')->nullable()->after('name');
        });
        
        // Fill in the crop_type for existing records by extracting it from the name
        $varieties = SeedVariety::all();
        foreach ($varieties as $variety) {
            // Extract the crop type (usually the first part before the dash)
            $nameParts = explode(' - ', $variety->name);
            if (count($nameParts) > 1) {
                $cropType = trim($nameParts[0]);
            } else {
                // Try to extract from dash or space
                $nameParts = explode('-', $variety->name);
                if (count($nameParts) > 1) {
                    $cropType = trim($nameParts[0]);
                } else {
                    // Just use the first word if we can't extract it
                    $nameParts = explode(' ', $variety->name);
                    $cropType = trim($nameParts[0]);
                }
            }
            
            $variety->crop_type = $cropType;
            $variety->save();
        }
        
        // Keep crop_type field nullable
        // We don't want to make it required since we're not using it
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_varieties', function (Blueprint $table) {
            $table->dropColumn('crop_type');
        });
    }
};
