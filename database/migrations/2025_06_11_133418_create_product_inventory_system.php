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
        // Create product_inventories table for batch/lot tracking
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_variation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable()->index();
            $table->string('lot_number')->nullable()->index();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->virtualAs('quantity - reserved_quantity');
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->date('expiration_date')->nullable()->index();
            $table->date('production_date')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'depleted', 'expired', 'damaged'])->default('active');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'expiration_date']);
            $table->index(['product_id', 'available_quantity']);
        });

        // Create inventory_transactions table for tracking all inventory movements
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'production',      // New stock from production
                'purchase',        // Direct purchase of finished goods
                'sale',           // Sale to customer
                'return',         // Customer return
                'adjustment',     // Manual adjustment
                'damage',         // Damaged goods
                'expiration',     // Expired goods
                'transfer',       // Transfer between locations
                'reservation',    // Reserve for order
                'release'         // Release reservation
            ]);
            $table->decimal('quantity', 10, 2); // Positive for in, negative for out
            $table->decimal('balance_after', 10, 2); // Running balance
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_type')->nullable(); // order, invoice, production_batch, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // For additional tracking data
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['product_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });

        // Add inventory tracking fields to products table
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('total_stock', 10, 2)->default(0)->after('active');
            $table->decimal('reserved_stock', 10, 2)->default(0)->after('total_stock');
            $table->decimal('available_stock', 10, 2)->virtualAs('total_stock - reserved_stock')->after('reserved_stock');
            $table->decimal('reorder_threshold', 10, 2)->default(0)->after('available_stock');
            $table->boolean('track_inventory')->default(true)->after('reorder_threshold');
            $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock', 'discontinued'])->default('in_stock')->after('track_inventory');
        });

        // Create inventory_reservations table for order reservations
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_products')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('expires_at');
        });

        // Create a view for easy inventory status checking
        DB::statement("
            CREATE VIEW product_inventory_summary AS
            SELECT 
                p.id as product_id,
                p.name as product_name,
                p.sku,
                COALESCE(SUM(pi.quantity), 0) as total_quantity,
                COALESCE(SUM(pi.reserved_quantity), 0) as total_reserved,
                COALESCE(SUM(pi.quantity - pi.reserved_quantity), 0) as total_available,
                p.reorder_threshold,
                CASE 
                    WHEN COALESCE(SUM(pi.quantity - pi.reserved_quantity), 0) <= 0 THEN 'out_of_stock'
                    WHEN COALESCE(SUM(pi.quantity - pi.reserved_quantity), 0) <= p.reorder_threshold THEN 'low_stock'
                    ELSE 'in_stock'
                END as calculated_status,
                MIN(CASE WHEN pi.expiration_date IS NOT NULL THEN pi.expiration_date END) as earliest_expiration,
                COUNT(DISTINCT pi.id) as batch_count
            FROM products p
            LEFT JOIN product_inventories pi ON p.id = pi.product_id AND pi.status = 'active'
            WHERE p.track_inventory = true
            GROUP BY p.id, p.name, p.sku, p.reorder_threshold
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS product_inventory_summary");
        
        Schema::dropIfExists('inventory_reservations');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'total_stock',
                'reserved_stock',
                'available_stock',
                'reorder_threshold',
                'track_inventory',
                'stock_status'
            ]);
        });
        
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('product_inventories');
    }
};