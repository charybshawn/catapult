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
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('consumable_type_id')->nullable();
            $table->unsignedBigInteger('consumable_unit_id')->nullable();
            $table->enum('type', ['packaging','soil','seed','label','other']);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('packaging_type_id')->nullable();
            $table->unsignedBigInteger('seed_entry_id')->nullable();
            $table->unsignedBigInteger('master_seed_catalog_id')->nullable();
            $table->unsignedBigInteger('master_cultivar_id')->nullable();
            $table->string('cultivar', 255)->nullable();
            $table->decimal('initial_stock', 10, 3)->default(0.000);
            $table->decimal('current_stock', 10, 2)->default(0.00);
            $table->enum('unit', ['unit','gram','pound','ounce','bag','tray','gallon','litre','millilitre']);
            $table->decimal('units_quantity', 10, 2)->default(1.00);
            $table->decimal('restock_threshold', 10, 2)->default(0.00);
            $table->decimal('restock_quantity', 10, 2)->default(0.00);
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->decimal('quantity_per_unit', 10, 2)->default(1.00);
            $table->string('quantity_unit', 255)->nullable();
            $table->decimal('total_quantity', 10, 2)->default(0.00);
            $table->decimal('consumed_quantity', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->string('lot_no', 255)->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamp('last_ordered_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};