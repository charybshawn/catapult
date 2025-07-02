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
        Schema::create('consumable_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('symbol', 20)->nullable(); // kg, lb, L, etc.
            $table->text('description')->nullable();
            $table->string('category', 50); // weight, volume, count
            $table->decimal('conversion_factor', 20, 10)->nullable(); // Convert to base unit
            $table->string('base_unit', 50)->nullable(); // gram, litre, unit
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'category', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_units');
    }
};
