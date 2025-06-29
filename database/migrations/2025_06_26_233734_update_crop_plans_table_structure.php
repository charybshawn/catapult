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
        Schema::table('crop_plans', function (Blueprint $table) {
            // Remove old columns that don't match the application
            $table->dropColumn(['name', 'target_date']);
            
            // Add new columns that the application expects
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('recipe_id')->nullable()->constrained('recipes')->onDelete('set null');
            $table->integer('trays_needed')->default(0);
            $table->decimal('grams_needed', 8, 2)->default(0);
            $table->decimal('grams_per_tray', 8, 2)->default(0);
            $table->date('plant_by_date')->nullable();
            $table->date('expected_harvest_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->json('calculation_details')->nullable();
            $table->json('order_items_included')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Update status enum to match application expectations
            $table->dropColumn('status');
        });
        
        // Re-add status with correct values
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'approved', 'generating', 'completed', 'cancelled'])->default('draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crop_plans', function (Blueprint $table) {
            // Restore original columns
            $table->string('name');
            $table->date('target_date');
            
            // Remove new columns
            $table->dropForeign(['order_id']);
            $table->dropForeign(['recipe_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'order_id', 'recipe_id', 'trays_needed', 'grams_needed', 'grams_per_tray',
                'plant_by_date', 'expected_harvest_date', 'delivery_date',
                'calculation_details', 'order_items_included', 'approved_by', 'approved_at', 'admin_notes'
            ]);
            
            // Restore original status enum
            $table->dropColumn('status');
        });
        
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
        });
    }
};