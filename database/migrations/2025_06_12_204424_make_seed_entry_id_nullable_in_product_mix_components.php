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
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Make seed_entry_id nullable since we're transitioning to master_cultivar_id
            $table->foreignId('seed_entry_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            // Revert back to non-nullable (though this might fail if there are NULL values)
            $table->foreignId('seed_entry_id')->nullable(false)->change();
        });
    }
};