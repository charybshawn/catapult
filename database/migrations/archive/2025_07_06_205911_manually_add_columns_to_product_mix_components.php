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
            if (!Schema::hasColumn('product_mix_components', 'master_seed_catalog_id')) {
                $table->bigInteger('master_seed_catalog_id')->unsigned()->nullable()->after('product_mix_id');
                $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog');
            }
            
            if (!Schema::hasColumn('product_mix_components', 'cultivar')) {
                $table->string('cultivar')->nullable()->after('master_seed_catalog_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->dropForeign(['master_seed_catalog_id']);
            $table->dropColumn(['master_seed_catalog_id', 'cultivar']);
        });
    }
};
