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
            $table->string('name', 255);
            $table->bigInteger('type_category_id');
            $table->bigInteger('unit_type_id');
            $table->decimal('capacity_volume', 10, 2)->nullable();
            $table->string('volume_unit', 20)->nullable();
            $table->text('description')->nullable();
            $table->integer('is_active')->default(1);
            $table->decimal('cost_per_unit', 10, 2)->nullable();
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