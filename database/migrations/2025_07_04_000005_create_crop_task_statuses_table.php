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
        Schema::create('crop_task_statuses', function (Blueprint $table) {
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

        // Insert default statuses
        DB::table('crop_task_statuses')->insert([
            ['code' => 'pending', 'name' => 'Pending', 'description' => 'Task is waiting to be completed', 'color' => 'yellow', 'sort_order' => 1],
            ['code' => 'completed', 'name' => 'Completed', 'description' => 'Task has been completed', 'color' => 'green', 'sort_order' => 2],
            ['code' => 'skipped', 'name' => 'Skipped', 'description' => 'Task was skipped', 'color' => 'gray', 'sort_order' => 3],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_task_statuses');
    }
};