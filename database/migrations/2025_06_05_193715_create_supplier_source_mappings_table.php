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
        Schema::create('supplier_source_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_url', 500); // The original source URL from scraper
            $table->string('domain', 255); // Extracted domain (e.g., "damseeds.com")
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true); // Allow disabling mappings
            $table->json('metadata')->nullable(); // Store additional matching info
            $table->timestamps();
            
            // Ensure unique mapping per domain
            $table->unique(['domain', 'supplier_id']);
            
            // Index for fast lookups
            $table->index('domain');
            $table->index('source_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_mappings');
    }
};
