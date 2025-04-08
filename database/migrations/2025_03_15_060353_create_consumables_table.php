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
            $table->enum('type', ['packaging', 'label', 'other']);
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('current_stock');
            $table->string('unit')->default('unit');
            $table->integer('restock_threshold');
            $table->integer('restock_quantity');
            $table->decimal('cost_per_unit', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
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
