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
            $table->string('supplier_product_title')->after('cultivar_name');
            $table->string('supplier_product_url')->after('supplier_sku');
            $table->string('image_url')->nullable()->after('supplier_product_url');
            $table->text('description')->nullable()->after('image_url');
            $table->json('tags')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_entries', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_product_title',
                'supplier_product_url', 
                'image_url',
                'description',
                'tags'
            ]);
        });
    }
};
