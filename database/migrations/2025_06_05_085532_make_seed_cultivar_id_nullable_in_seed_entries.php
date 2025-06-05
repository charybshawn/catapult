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
        Schema::table('seed_entries', function (Blueprint $table) {
            // Make seed_cultivar_id nullable to support the transition away from SeedCultivar
            $table->unsignedBigInteger('seed_cultivar_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_entries', function (Blueprint $table) {
            // Restore seed_cultivar_id as required
            $table->unsignedBigInteger('seed_cultivar_id')->nullable(false)->change();
        });
    }
};
