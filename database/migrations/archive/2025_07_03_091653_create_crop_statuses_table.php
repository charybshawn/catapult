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
        Schema::create('crop_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 50)->default('gray');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_final')->default(false);
            $table->boolean('allows_modifications')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Seed the default crop statuses
        $statuses = [
            ['code' => 'not_started', 'name' => 'Not Started', 'color' => 'gray', 'sort_order' => 1],
            ['code' => 'planted', 'name' => 'Planted', 'color' => 'blue', 'sort_order' => 2],
            ['code' => 'growing', 'name' => 'Growing', 'color' => 'yellow', 'sort_order' => 3],
            ['code' => 'ready_to_harvest', 'name' => 'Ready to Harvest', 'color' => 'orange', 'sort_order' => 4],
            ['code' => 'harvested', 'name' => 'Harvested', 'color' => 'green', 'sort_order' => 5, 'is_final' => true],
            ['code' => 'na', 'name' => 'N/A', 'color' => 'gray', 'sort_order' => 6, 'allows_modifications' => false],
        ];

        foreach ($statuses as $status) {
            DB::table('crop_statuses')->insert([
                'code' => $status['code'],
                'name' => $status['name'],
                'color' => $status['color'],
                'sort_order' => $status['sort_order'],
                'is_final' => $status['is_final'] ?? false,
                'allows_modifications' => $status['allows_modifications'] ?? true,
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
        Schema::dropIfExists('crop_statuses');
    }
};