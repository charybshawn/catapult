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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('master_seed_catalog_id')->nullable()->after('seed_entry_id');
            $table->foreign('master_seed_catalog_id')->references('id')->on('master_seed_catalog')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['master_seed_catalog_id']);
            $table->dropColumn('master_seed_catalog_id');
        });
    }
};
