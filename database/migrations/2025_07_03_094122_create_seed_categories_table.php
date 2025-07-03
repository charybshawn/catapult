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
        Schema::create('seed_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 50)->default('gray');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Seed the default seed categories
        $categories = [
            ['code' => 'herbs', 'name' => 'Herbs', 'color' => 'green', 'sort_order' => 1],
            ['code' => 'brassicas', 'name' => 'Brassicas', 'color' => 'blue', 'sort_order' => 2],
            ['code' => 'legumes', 'name' => 'Legumes', 'color' => 'purple', 'sort_order' => 3],
            ['code' => 'greens', 'name' => 'Greens', 'color' => 'emerald', 'sort_order' => 4],
            ['code' => 'grains', 'name' => 'Grains', 'color' => 'yellow', 'sort_order' => 5],
            ['code' => 'shoots', 'name' => 'Shoots', 'color' => 'lime', 'sort_order' => 6],
            ['code' => 'other', 'name' => 'Other', 'color' => 'gray', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            DB::table('seed_categories')->insert([
                'code' => $category['code'],
                'name' => $category['name'],
                'color' => $category['color'],
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_categories');
    }
};