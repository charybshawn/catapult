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
        Schema::create('crop_batches', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('recipe_id');
                    $table->bigInteger('order_id')->nullable();
                    $table->bigInteger('crop_plan_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_batches');
    }
};
