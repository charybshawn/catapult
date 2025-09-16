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
                    $table->id('id');
                    $table->bigInteger('order_id')->nullable();
                    $table->decimal('amount', 10, 2);
                    $table->bigInteger('payment_status_id')->nullable();
                    $table->timestamp('sent_at')->nullable();
                    $table->timestamp('paid_at')->nullable();
                    $table->string('due_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('invoice_number', 255);
                    $table->text('notes')->nullable();
                    $table->integer('is_consolidated');
                    $table->integer('consolidated_order_count');
                    $table->json('consolidated_order_ids')->nullable();
                    $table->bigInteger('customer_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
