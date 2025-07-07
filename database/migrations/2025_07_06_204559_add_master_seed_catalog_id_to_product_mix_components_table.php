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
            $table->bigInteger('master_seed_catalog_id')->unsigned()->nullable()->after('product_mix_id');
            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mix_components', function (Blueprint $table) {
            $table->dropForeign(['master_seed_catalog_id']);
            $table->dropColumn('master_seed_catalog_id');
        });
    }
};