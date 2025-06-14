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
        Schema::table('consumables', function (Blueprint $table) {
            $table->string('cultivar')->nullable()->after('master_seed_catalog_id');
            
            // Add index for faster queries
            $table->index(['master_seed_catalog_id', 'cultivar']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropIndex(['master_seed_catalog_id', 'cultivar']);
            $table->dropColumn('cultivar');
        });
    }
};