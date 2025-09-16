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
        Schema::create('crop_plans_aggregate', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('variety_id');
                    $table->string('harvest_date'); // TODO: Review type for: date not null
                    $table->decimal('total_grams_needed', 10, 2);
                    $table->integer('total_trays_needed');
                    $table->decimal('grams_per_tray', 8, 2);
                    $table->string('plant_date'); // TODO: Review type for: date not null
                    $table->string('seed_soak_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('status', 50);
                    $table->json('calculation_details')->nullable();
                    $table->bigInteger('created_by');
                    $table->bigInteger('updated_by')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans_aggregate');
    }
};
