<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crop_task_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 20)->default('gray');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // Insert default types
        DB::table('crop_task_types')->insert([
            ['code' => 'water', 'name' => 'Water', 'description' => 'Watering task for crop', 'color' => 'blue', 'sort_order' => 1],
            ['code' => 'advance_stage', 'name' => 'Advance Stage', 'description' => 'Move crop to next growth stage', 'color' => 'purple', 'sort_order' => 2],
            ['code' => 'harvest', 'name' => 'Harvest', 'description' => 'Harvest the crop', 'color' => 'green', 'sort_order' => 3],
            ['code' => 'general', 'name' => 'General', 'description' => 'General maintenance task', 'color' => 'gray', 'sort_order' => 4],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_task_types');
    }
};