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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->bigInteger('payment_status_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->string('invoice_number', 255);
            $table->text('notes')->nullable();
            $table->integer('is_consolidated')->default(0);
            $table->integer('consolidated_order_count')->default(1);
            $table->json('consolidated_order_ids')->nullable();
            $table->bigInteger('customer_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};