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
        // Ensure seed_varieties table exists
        if (!Schema::hasTable('seed_varieties')) {
            throw new \Exception('The seed_varieties table must exist before creating product mixes.');
        }

        // Drop existing tables if they exist, handling foreign key constraints
        if (Schema::hasTable('product_mix_seed_variety')) {
            Schema::table('product_mix_seed_variety', function (Blueprint $table) {
                $table->dropForeign(['product_mix_id']);
            });
            Schema::dropIfExists('product_mix_seed_variety');
        }
        
        if (Schema::hasTable('product_mix_components')) {
            Schema::table('product_mix_components', function (Blueprint $table) {
                $table->dropForeign(['product_mix_id']);
            });
            Schema::dropIfExists('product_mix_components');
        }
        
        Schema::dropIfExists('product_mixes');
        
        Schema::create('product_mixes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_mix_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_mix_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seed_variety_id')->constrained()->cascadeOnDelete();
            $table->decimal('percentage', 5, 2); // Allows for precise percentages (e.g., 33.33%)
            $table->timestamps();
            
            // Ensure each mix has only one entry per variety
            $table->unique(['product_mix_id', 'seed_variety_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_mix_components');
        Schema::dropIfExists('product_mixes');
    }
}; 