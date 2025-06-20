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
        Schema::create('crop_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('target_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['status', 'target_date']);
        });

        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('crop_plan_id')->nullable()->constrained('crop_plans')->onDelete('set null');
            $table->string('tray_number');
            $table->timestamp('planted_at')->nullable();
            $table->enum('current_stage', ['germination', 'blackout', 'light', 'harvested'])->default('germination');
            $table->timestamp('stage_updated_at')->nullable();
            $table->timestamp('planting_at')->nullable();
            $table->timestamp('germination_at')->nullable();
            $table->timestamp('blackout_at')->nullable();
            $table->timestamp('light_at')->nullable();
            $table->timestamp('harvested_at')->nullable();
            $table->decimal('harvest_weight_grams', 8, 2)->nullable();
            $table->timestamp('watering_suspended_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('time_to_next_stage_minutes')->nullable();
            $table->string('time_to_next_stage_status', 20)->nullable();
            $table->integer('stage_age_minutes')->nullable();
            $table->string('stage_age_status', 20)->nullable();
            $table->integer('total_age_minutes')->nullable();
            $table->string('total_age_status', 20)->nullable();
            $table->timestamps();
            
            $table->index(['tray_number', 'current_stage']);
            $table->index('recipe_id');
            $table->index('order_id');
            $table->index('crop_plan_id');
            $table->index('current_stage');
            $table->index('planted_at');
        });

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

        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_cultivar_id')->constrained('master_cultivars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_weight_grams', 10, 2);
            $table->integer('tray_count');
            $table->decimal('average_weight_per_tray', 10, 2)->storedAs('total_weight_grams / NULLIF(tray_count, 0)');
            $table->date('harvest_date');
            $table->date('week_start_date')->storedAs('DATE_SUB(harvest_date, INTERVAL WEEKDAY(harvest_date) DAY)');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['master_cultivar_id', 'harvest_date']);
            $table->index('week_start_date');
        });

        // Create the crop_batches view
        DB::statement('
            CREATE OR REPLACE VIEW crop_batches AS
            SELECT 
                MIN(id) as id,
                recipe_id,
                DATE(planted_at) as planting_date,
                current_stage,
                COUNT(*) as tray_count
            FROM crops
            WHERE planted_at IS NOT NULL
            GROUP BY recipe_id, DATE(planted_at), current_stage
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS crop_batches');
        Schema::dropIfExists('harvests');
        Schema::dropIfExists('crop_tasks');
        Schema::dropIfExists('crops');
        Schema::dropIfExists('crop_plans');
    }
};