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
        // Drop the crop_tasks table as it has been replaced by task_schedules
        Schema::dropIfExists('crop_tasks');
        
        // Also drop the related lookup tables if they exist
        Schema::dropIfExists('crop_task_types');
        Schema::dropIfExists('crop_task_statuses');
        
        // Mark the old migration as completed to prevent re-running
        if (Schema::hasTable('migrations')) {
            DB::table('migrations')->insertOrIgnore([
                'migration' => '2025_05_03_224138_create_crop_tasks_table',
                'batch' => 0
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the crop_tasks table
        Schema::create('crop_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->onDelete('cascade');
            $table->enum('task_type', ['water', 'advance_stage', 'harvest', 'general']);
            $table->text('description');
            $table->timestamp('scheduled_for');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'completed', 'skipped'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['crop_id', 'status']);
            $table->index(['scheduled_for', 'status']);
        });
    }
};