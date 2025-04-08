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
        Schema::create('packaging_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity_grams');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('cost_per_unit', 8, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packaging_types');
    }
};
