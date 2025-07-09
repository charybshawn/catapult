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
            // Add columns from main branch that are missing in develop
            if (!Schema::hasColumn('crops', 'expected_harvest_at')) {
                $table->timestamp('expected_harvest_at')->nullable()->after('total_age_display');
            }
            
            if (!Schema::hasColumn('crops', 'tray_count')) {
                $table->unsignedInteger('tray_count')->default(1)->after('expected_harvest_at');
            }
            
            if (!Schema::hasColumn('crops', 'tray_numbers')) {
                $table->string('tray_numbers')->nullable()->after('tray_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn(['expected_harvest_at', 'tray_count', 'tray_numbers']);
        });
    }
};