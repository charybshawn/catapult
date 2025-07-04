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
        Schema::create('recipe_stage_types', function (Blueprint $table) {
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

        // Insert default stages
        DB::table('recipe_stage_types')->insert([
            ['code' => 'germination', 'name' => 'Germination', 'description' => 'Initial seed germination stage', 'color' => 'green', 'sort_order' => 1],
            ['code' => 'blackout', 'name' => 'Blackout', 'description' => 'Dark growth period for root development', 'color' => 'gray', 'sort_order' => 2],
            ['code' => 'light', 'name' => 'Light', 'description' => 'Light exposure stage for greening', 'color' => 'yellow', 'sort_order' => 3],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_stage_types');
    }
};