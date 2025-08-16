<?php

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
        Schema::table('crops', function (Blueprint $table) {
            // Add missing timestamp fields
            $table->timestamp('harvested_at')->nullable()->after('light_at');
            
            // Add tray_count field if not already exists
            if (!Schema::hasColumn('crops', 'tray_count')) {
                $table->integer('tray_count')->nullable()->after('tray_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn(['harvested_at', 'tray_count']);
        });
    }
};
