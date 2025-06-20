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
        // Master seed catalog
        Schema::create('master_seed_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('common_name')->unique();
            $table->json('cultivars')->nullable();
            $table->string('category')->nullable();
            $table->json('aliases')->nullable();
            $table->text('growing_notes')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('common_name');
            $table->index('category');
            $table->index('is_active');
        });

        // Master cultivars
        Schema::create('master_cultivars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_seed_catalog_id')->constrained('master_seed_catalog')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->integer('days_to_maturity')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['master_seed_catalog_id', 'name']);
            $table->index('supplier_id');
        });

        // Seed cultivars
        Schema::create('seed_cultivars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_catalog_id')->constrained('master_seed_catalog')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('crop_type')->nullable();
            $table->integer('days_to_maturity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed entries
        Schema::create('seed_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_cultivar_id')->nullable()->constrained('seed_cultivars')->onDelete('cascade');
            $table->string('common_name')->nullable();
            $table->string('cultivar_name')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('supplier_sku')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('seed_cultivar_id');
            $table->index('supplier_id');
            $table->index(['supplier_id', 'supplier_sku']);
        });

        // Seed variations
        Schema::create('seed_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_entry_id')->constrained('seed_entries')->onDelete('cascade');
            $table->unsignedBigInteger('consumable_id')->nullable()->index();
            $table->string('size');
            $table->string('unit');
            $table->decimal('current_price', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            
            $table->index('seed_entry_id');
        });

        // Seed price history
        Schema::create('seed_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_variation_id')->constrained('seed_variations')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['seed_variation_id', 'checked_at']);
        });

        // Seed scrape uploads
        Schema::create('seed_scrape_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('filename');
            $table->integer('total_entries')->default(0);
            $table->integer('new_entries')->default(0);
            $table->integer('updated_entries')->default(0);
            $table->json('failed_entries')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });

        // Supplier source mappings
        Schema::create('supplier_source_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('source_name');
            $table->string('source_identifier');
            $table->json('mapping_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['supplier_id', 'source_name', 'source_identifier'], 'supplier_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_mappings');
        Schema::dropIfExists('seed_scrape_uploads');
        Schema::dropIfExists('seed_price_history');
        Schema::dropIfExists('seed_variations');
        Schema::dropIfExists('seed_entries');
        Schema::dropIfExists('seed_cultivars');
        Schema::dropIfExists('master_cultivars');
        Schema::dropIfExists('master_seed_catalog');
    }
};