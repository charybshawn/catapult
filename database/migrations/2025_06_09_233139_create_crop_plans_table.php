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
        Schema::create('crop_plans', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            
            // Plan details
            $table->string('status')->default('draft'); // draft, approved, generating, completed, cancelled
            $table->integer('trays_needed')->default(1);
            $table->decimal('grams_needed', 8, 2);
            $table->decimal('grams_per_tray', 8, 2)->nullable();
            
            // Timing
            $table->date('plant_by_date');
            $table->date('expected_harvest_date');
            $table->date('delivery_date');
            
            // Calculation details (for transparency)
            $table->json('calculation_details')->nullable(); // Store how the plan was calculated
            $table->json('order_items_included')->nullable(); // Which order items this plan covers
            
            // User management
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id', 'recipe_id']);
            $table->index(['status', 'plant_by_date']);
            $table->index('plant_by_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans');
    }
};
