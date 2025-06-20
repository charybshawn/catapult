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
        Schema::create('packaging_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('volume', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['packaging', 'soil', 'seed', 'label', 'other']);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types')->onDelete('set null');
            $table->foreignId('seed_entry_id')->nullable()->constrained('seed_entries')->onDelete('set null');
            $table->foreignId('master_seed_catalog_id')->nullable()->constrained('master_seed_catalog')->onDelete('set null');
            $table->foreignId('master_cultivar_id')->nullable()->constrained('master_cultivars')->onDelete('set null');
            $table->string('cultivar')->nullable();
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->enum('unit', ['unit', 'gram', 'pound', 'ounce', 'bag', 'tray', 'gallon', 'litre', 'millilitre']);
            $table->decimal('units_quantity', 10, 2)->default(1);
            $table->decimal('restock_threshold', 10, 2)->default(0);
            $table->decimal('restock_quantity', 10, 2)->default(0);
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->decimal('quantity_per_unit', 10, 2)->default(1);
            $table->string('quantity_unit')->nullable();
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->decimal('consumed_quantity', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('lot_no')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_ordered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('type');
            $table->index('supplier_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumables');
        Schema::dropIfExists('packaging_types');
    }
};