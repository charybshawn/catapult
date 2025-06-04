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
        Schema::create('product_mix_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_mix_id')->constrained()->onDelete('cascade');
            $table->foreignId('seed_variety_id')->constrained()->onDelete('cascade');
            $table->decimal('percentage', 5, 2); // Allows values like 100.00
            $table->timestamps();
            
            $table->unique(['product_mix_id', 'seed_variety_id']);
            $table->index(['product_mix_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_mix_components');
    }
};
