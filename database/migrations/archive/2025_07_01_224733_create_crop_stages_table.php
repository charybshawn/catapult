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
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 50)->default('gray'); // For UI badges
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('typical_duration_days')->nullable(); // Expected duration for this stage
            $table->boolean('requires_light')->default(false); // Environmental requirements
            $table->boolean('requires_watering')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_stages');
    }
};
