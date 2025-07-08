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
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('symbol', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->decimal('conversion_factor', 20, 10)->nullable();
            $table->string('base_unit', 50)->nullable();
            $table->integer('is_active')->default(1);
            $table->integer('sort_order')->default(0);
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