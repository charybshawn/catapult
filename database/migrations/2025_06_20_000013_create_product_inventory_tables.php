<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('price_variation_id')->nullable()->constrained('product_price_variations')->onDelete('set null');
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->storedAs('quantity - reserved_quantity');
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('production_date')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'depleted', 'expired', 'damaged'])->default('active');
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'price_variation_id']);
            $table->index('batch_number');
            $table->index('lot_number');
            $table->index('expiration_date');
            $table->index(['product_id', 'available_quantity']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_inventory_id')->nullable()->constrained('product_inventories')->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return', 'damage', 'transfer']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_inventory_id')->constrained('product_inventories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('expires_at');
        });

        // Create product inventory summary view
        DB::statement('
            CREATE OR REPLACE VIEW product_inventory_summary AS
            SELECT 
                product_id,
                SUM(quantity) as total_quantity,
                SUM(reserved_quantity) as total_reserved,
                SUM(available_quantity) as total_available,
                AVG(cost_per_unit) as avg_cost,
                MIN(expiration_date) as earliest_expiration,
                COUNT(DISTINCT batch_number) as batch_count,
                COUNT(*) as location_count
            FROM product_inventories
            WHERE status = "active"
            GROUP BY product_id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS product_inventory_summary');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('product_inventories');
    }
};