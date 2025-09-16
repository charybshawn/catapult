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
        Schema::create('crop_plans', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('aggregated_crop_plan_id')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('status_id');
            $table->bigInteger('created_by');
            $table->bigInteger('order_id')->nullable();
            $table->bigInteger('recipe_id')->nullable();
            $table->bigInteger('variety_id')->nullable();
            $table->integer('trays_needed')->default(0);
            $table->decimal('grams_needed', 8, 2)->default(0.00);
            $table->decimal('grams_per_tray', 8, 2)->default(0.00);
            $table->date('plant_by_date')->nullable();
            $table->date('seed_soak_date')->nullable();
            $table->date('expected_harvest_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->json('calculation_details')->nullable();
            $table->json('order_items_included')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->integer('is_missing_recipe')->default(0);
            $table->string('missing_recipe_notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans');
    }
};