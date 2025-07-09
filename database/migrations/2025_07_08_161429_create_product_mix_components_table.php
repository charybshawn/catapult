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
            $table->unsignedBigInteger('product_mix_id');
            $table->unsignedBigInteger('master_seed_catalog_id')->nullable();
            $table->decimal('percentage', 8, 5);
            $table->string('cultivar', 255)->nullable();
            $table->timestamps();
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