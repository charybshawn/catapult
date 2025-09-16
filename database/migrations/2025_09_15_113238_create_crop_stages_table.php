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
        Schema::create('crop_stages', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('code', 50);
                    $table->string('name', 100);
                    $table->text('description')->nullable();
                    $table->string('color', 50);
                    $table->integer('is_active');
                    $table->integer('sort_order');
                    $table->integer('typical_duration_days')->nullable();
                    $table->integer('requires_light');
                    $table->integer('requires_watering');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_stages');
    }
};
