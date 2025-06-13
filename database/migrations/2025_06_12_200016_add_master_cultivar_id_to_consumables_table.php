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
            $table->foreignId('master_cultivar_id')
                ->nullable()
                ->after('master_seed_catalog_id')
                ->constrained('master_cultivars')
                ->onDelete('restrict');
            
            $table->index(['master_seed_catalog_id', 'master_cultivar_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['master_cultivar_id']);
            $table->dropIndex(['master_seed_catalog_id', 'master_cultivar_id']);
            $table->dropColumn('master_cultivar_id');
        });
    }
};
