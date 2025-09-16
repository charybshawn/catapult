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
        Schema::create('crop_harvest', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('crop_id');
                    $table->bigInteger('harvest_id');
                    $table->decimal('harvested_weight_grams', 8, 2);
                    $table->decimal('percentage_harvested', 5, 2);
                    $table->text('notes')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_harvest');
    }
};
