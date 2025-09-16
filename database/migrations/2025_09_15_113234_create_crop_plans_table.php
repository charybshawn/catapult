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
                    $table->id('id');
                    $table->bigInteger('aggregated_crop_plan_id')->nullable();
                    $table->text('notes')->nullable();
                    $table->bigInteger('status_id');
                    $table->bigInteger('created_by');
                    $table->bigInteger('order_id')->nullable();
                    $table->bigInteger('recipe_id')->nullable();
                    $table->bigInteger('variety_id')->nullable();
                    $table->integer('trays_needed');
                    $table->decimal('grams_needed', 8, 2);
                    $table->decimal('grams_per_tray', 8, 2);
                    $table->string('plant_by_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('seed_soak_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('expected_harvest_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('delivery_date')->nullable(); // TODO: Review type for: date default null
                    $table->json('calculation_details')->nullable();
                    $table->json('order_items_included')->nullable();
                    $table->bigInteger('approved_by')->nullable();
                    $table->timestamp('approved_at')->nullable();
                    $table->text('admin_notes')->nullable();
                    $table->integer('is_missing_recipe');
                    $table->string('missing_recipe_notes', 255)->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans');
    }
};
