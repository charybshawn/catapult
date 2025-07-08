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
        Schema::create('seed_entries', function (Blueprint $table) {
            $table->id();
            $table->string('common_name', 255)->nullable();
            $table->string('cultivar_name', 255)->nullable();
            $table->string('supplier_product_title', 255);
            $table->unsignedBigInteger('supplier_id');
            $table->string('supplier_sku', 255)->nullable();
            $table->string('supplier_product_url', 255);
            $table->string('image_url', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('url', 255)->nullable();
            $table->integer('is_active')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_entries');
    }
};