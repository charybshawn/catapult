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
                    $table->id('id');
                    $table->bigInteger('product_mix_id');
                    $table->bigInteger('master_seed_catalog_id')->nullable();
                    $table->decimal('percentage', 8, 5);
                    $table->string('cultivar', 255)->nullable();
                    $table->bigInteger('recipe_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_mix_components');
    }
};
