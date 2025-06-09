<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTE: This migration was originally for the seed_varieties table,
     * but that table was later replaced with seed_entries. Since the
     * seed_varieties table gets dropped in migration 2025_06_04_100005,
     * this migration is now a no-op to prevent migration errors.
     */
    public function up(): void
    {
        // Check if the seed_varieties table exists before trying to modify it
        if (Schema::hasTable('seed_varieties')) {
            Schema::table('seed_varieties', function (Blueprint $table) {
                $table->string('crop_type')->nullable()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the seed_varieties table exists before trying to modify it
        if (Schema::hasTable('seed_varieties')) {
            Schema::table('seed_varieties', function (Blueprint $table) {
                $table->dropColumn('crop_type');
            });
        }
    }
};
