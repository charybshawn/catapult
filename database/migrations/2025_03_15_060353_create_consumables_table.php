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
            $table->string('name');
            $table->enum('type', ['packaging', 'soil', 'seed', 'label', 'other']);
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('packaging_type_id')->nullable()
                ->comment('For packaging consumables only');
            $table->integer('current_stock');
            $table->string('unit')->default('unit');
            $table->integer('restock_threshold');
            $table->integer('restock_quantity');
            $table->decimal('cost_per_unit', 8, 2)->nullable();
            $table->decimal('quantity_per_unit', 10, 2)->nullable()->comment('Weight of each unit');
            $table->string('quantity_unit', 20)->nullable()->comment('Unit of measurement (g, kg, l, oz)');
            $table->decimal('total_quantity', 12, 2)->nullable()
                ->comment('Calculated field: current_stock * quantity_per_unit');
            $table->text('notes')->nullable();
            $table->string('lot_no', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_ordered_at')->nullable();
            $table->timestamps();
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
