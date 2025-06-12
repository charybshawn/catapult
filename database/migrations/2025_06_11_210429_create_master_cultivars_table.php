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
        Schema::create('master_cultivars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_seed_catalog_id')->constrained('master_seed_catalog')->onDelete('cascade');
            $table->string('cultivar_name')->index();
            $table->json('aliases')->nullable(); // Alternative cultivar names
            $table->text('description')->nullable();
            $table->text('growing_notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            
            $table->unique(['master_seed_catalog_id', 'cultivar_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_cultivars');
    }
};
