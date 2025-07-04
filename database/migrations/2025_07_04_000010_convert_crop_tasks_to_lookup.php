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
        // Add new foreign key columns
        Schema::table('crop_tasks', function (Blueprint $table) {
            $table->foreignId('crop_task_type_id')->nullable()->after('task_type');
            $table->foreignId('crop_task_status_id')->nullable()->after('status');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE crop_tasks 
            SET crop_task_type_id = (
                SELECT id FROM crop_task_types 
                WHERE crop_task_types.code = crop_tasks.task_type
            )
            WHERE task_type IS NOT NULL
        ");

        DB::statement("
            UPDATE crop_tasks 
            SET crop_task_status_id = (
                SELECT id FROM crop_task_statuses 
                WHERE crop_task_statuses.code = crop_tasks.status
            )
            WHERE status IS NOT NULL
        ");

        // Make the foreign keys non-nullable and add constraints
        Schema::table('crop_tasks', function (Blueprint $table) {
            $table->foreignId('crop_task_type_id')->nullable(false)->change();
            $table->foreignId('crop_task_status_id')->nullable(false)->change();
            $table->foreign('crop_task_type_id')->references('id')->on('crop_task_types');
            $table->foreign('crop_task_status_id')->references('id')->on('crop_task_statuses');
        });

        // Drop old enum columns
        Schema::table('crop_tasks', function (Blueprint $table) {
            $table->dropColumn(['task_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back enum columns
        Schema::table('crop_tasks', function (Blueprint $table) {
            $table->enum('task_type', ['water', 'advance_stage', 'harvest', 'general'])->after('crop_task_type_id');
            $table->enum('status', ['pending', 'completed', 'skipped'])->default('pending')->after('crop_task_status_id');
        });

        // Copy data back from foreign keys to enums
        DB::statement("
            UPDATE crop_tasks 
            SET task_type = (
                SELECT code FROM crop_task_types 
                WHERE crop_task_types.id = crop_tasks.crop_task_type_id
            )
            WHERE crop_task_type_id IS NOT NULL
        ");

        DB::statement("
            UPDATE crop_tasks 
            SET status = (
                SELECT code FROM crop_task_statuses 
                WHERE crop_task_statuses.id = crop_tasks.crop_task_status_id
            )
            WHERE crop_task_status_id IS NOT NULL
        ");

        // Drop foreign keys and columns
        Schema::table('crop_tasks', function (Blueprint $table) {
            $table->dropForeign(['crop_task_type_id']);
            $table->dropForeign(['crop_task_status_id']);
            $table->dropColumn(['crop_task_type_id', 'crop_task_status_id']);
        });
    }
};