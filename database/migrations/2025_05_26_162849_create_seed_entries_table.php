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
            $table->foreignId('seed_cultivar_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('supplier_product_title');
            $table->string('supplier_product_url');
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->unique(['supplier_id', 'supplier_product_url']);
            $table->timestamps();
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
