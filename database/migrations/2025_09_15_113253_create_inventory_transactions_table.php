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
        Schema::create('inventory_transactions', function (Blueprint $table) {
                    $table->id('id');
                    $table->bigInteger('product_inventory_id')->nullable();
                    $table->bigInteger('product_id');
                    $table->bigInteger('inventory_transaction_type_id');
                    $table->decimal('quantity', 10, 2);
                    $table->decimal('balance_after', 10, 2);
                    $table->decimal('unit_cost', 10, 2)->nullable();
                    $table->decimal('total_cost', 10, 2)->nullable();
                    $table->string('reference_type', 255)->nullable();
                    $table->bigInteger('reference_id')->nullable();
                    $table->bigInteger('user_id');
                    $table->text('notes')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
