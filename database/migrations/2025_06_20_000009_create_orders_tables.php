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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('harvest_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_production', 'ready_for_harvest', 'harvested', 'packed', 'delivered', 'cancelled'])->default('pending');
            $table->enum('order_status', ['pending', 'confirmed', 'in_production', 'ready_for_harvest', 'harvested', 'packed', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->enum('delivery_status', ['pending', 'scheduled', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->enum('customer_type', ['retail', 'wholesale', 'b2b'])->nullable();
            $table->enum('order_type', ['standard', 'subscription', 'b2b'])->default('standard');
            $table->enum('order_classification', ['scheduled', 'ondemand', 'overflow', 'priority'])->default('scheduled');
            $table->string('billing_period')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('harvest_date');
            $table->index('delivery_date');
            $table->index('order_type');
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->string('invoice_number')->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_consolidated')->default(false);
            $table->json('consolidated_order_ids')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('due_date');
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['stripe', 'e-transfer', 'cash', 'invoice']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            });
        }

        if (!Schema::hasTable('order_packagings')) {
            Schema::create('order_packagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('packaging_type_id')->constrained('packaging_types')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
            
            $table->index('order_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_packagings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('orders');
    }
};