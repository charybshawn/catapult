<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packaging_types', function (Blueprint $table) {
            // Check if we need to rename volume to capacity_volume
            if (Schema::hasColumn('packaging_types', 'volume') && !Schema::hasColumn('packaging_types', 'capacity_volume')) {
                $table->renameColumn('volume', 'capacity_volume');
            }
            
            // Add volume_unit column if it doesn't exist
            if (!Schema::hasColumn('packaging_types', 'volume_unit')) {
                $table->string('volume_unit', 20)->nullable()->after('capacity_volume');
            }
            
            // Add type column for packaging categorization
            if (!Schema::hasColumn('packaging_types', 'type')) {
                $table->enum('type', ['clamshell', 'bag', 'box', 'jar', 'tray', 'bulk', 'other'])
                    ->default('other')
                    ->after('name');
            }
            
            // Add unit_type for weight vs count based packaging
            if (!Schema::hasColumn('packaging_types', 'unit_type')) {
                $table->enum('unit_type', ['count', 'weight'])
                    ->default('count')
                    ->after('type');
            }
        });
        
        // Update existing records with sensible defaults
        DB::table('packaging_types')->where('volume_unit', null)->update([
            'volume_unit' => 'oz'
        ]);
        
        // Set types based on names
        DB::table('packaging_types')->where('name', 'like', '%Clamshell%')->update(['type' => 'clamshell']);
        DB::table('packaging_types')->where('name', 'like', '%Bag%')->update(['type' => 'bag']);
        DB::table('packaging_types')->where('name', 'like', '%Box%')->update(['type' => 'box']);
        DB::table('packaging_types')->where('name', 'like', '%Jar%')->update(['type' => 'jar']);
        DB::table('packaging_types')->where('name', 'like', '%Tray%')->update(['type' => 'tray']);
        DB::table('packaging_types')->where('name', 'Bulk')->update(['type' => 'bulk', 'unit_type' => 'weight']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packaging_types', function (Blueprint $table) {
            if (Schema::hasColumn('packaging_types', 'unit_type')) {
                $table->dropColumn('unit_type');
            }
            if (Schema::hasColumn('packaging_types', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('packaging_types', 'volume_unit')) {
                $table->dropColumn('volume_unit');
            }
            if (Schema::hasColumn('packaging_types', 'capacity_volume')) {
                $table->renameColumn('capacity_volume', 'volume');
            }
        });
    }
};
