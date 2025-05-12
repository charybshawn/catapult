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
        // The crop_alerts table is not used yet, but is prepared for future transition
        // Currently, all crop alerts are stored in the task_schedules table
        Schema::create('crop_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type'); // The type of alert (advance_to_stage, suspend_watering, etc.)
            $table->json('conditions'); // JSON containing alert conditions
            $table->boolean('is_active')->default(true); // Whether this alert is active
            $table->timestamp('last_executed_at')->nullable(); // When the alert was last executed
            $table->timestamp('scheduled_for')->nullable(); // When the alert is scheduled to run
            $table->timestamps();
            
            // Add a comment to clarify that this table is not yet used
            $table->comment('Future table for crop alerts. Currently using task_schedules table.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_alerts');
    }
}; 