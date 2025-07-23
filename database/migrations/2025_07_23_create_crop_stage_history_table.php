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
        Schema::create('crop_stage_history', function (Blueprint $table) {
            $table->id();
            
            // Core relationships (what we're tracking)
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('crop_stages');
            
            // When it happened
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            
            // Optional context
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            
            // Laravel timestamps
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index(['crop_id', 'entered_at']);
            $table->index(['crop_batch_id', 'entered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_stage_history');
    }
};