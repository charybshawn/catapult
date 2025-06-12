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
        Schema::table('master_seed_catalog', function (Blueprint $table) {
            // Drop the temporary cultivars column if it exists
            if (Schema::hasColumn('master_seed_catalog', 'cultivars')) {
                $table->dropColumn('cultivars');
            }
            
            // Rename scientific_name to cultivars
            $table->renameColumn('scientific_name', 'cultivars');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_seed_catalog', function (Blueprint $table) {
            // Rename back to scientific_name
            $table->renameColumn('cultivars', 'scientific_name');
        });
    }
};
