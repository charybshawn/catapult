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
        // First, copy any planting_at values to germination_at where germination_at is null
        DB::statement('UPDATE crops SET germination_at = planting_at WHERE germination_at IS NULL AND planting_at IS NOT NULL');
        
        // Drop the planting_at column
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('planting_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->timestamp('planting_at')->nullable()->after('tray_number');
        });
        
        // Restore planting_at values from germination_at
        DB::statement('UPDATE crops SET planting_at = germination_at WHERE planting_at IS NULL');
    }
};
