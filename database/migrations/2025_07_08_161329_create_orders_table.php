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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('harvest_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->unsignedBigInteger('order_status_id')->nullable();
            $table->unsignedBigInteger('unified_status_id')->nullable();
            $table->unsignedBigInteger('crop_status_id')->nullable();
            $table->unsignedBigInteger('fulfillment_status_id')->nullable();
            $table->unsignedBigInteger('payment_status_id')->nullable();
            $table->unsignedBigInteger('delivery_status_id')->nullable();
            $table->enum('customer_type', ['retail','wholesale','b2b'])->nullable();
            $table->unsignedBigInteger('order_type_id')->nullable();
            $table->string('billing_frequency', 255)->nullable();
            $table->integer('requires_invoice')->default(0);
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            $table->unsignedBigInteger('consolidated_invoice_id')->nullable();
            $table->json('billing_preferences')->nullable();
            $table->unsignedBigInteger('order_classification_id')->nullable();
            $table->string('billing_period', 255)->nullable();
            $table->integer('is_recurring')->default(0);
            $table->unsignedBigInteger('parent_recurring_order_id')->nullable();
            $table->string('recurring_frequency', 255)->nullable();
            $table->date('recurring_start_date')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->integer('is_recurring_active')->default(1);
            $table->json('recurring_days_of_week')->nullable();
            $table->integer('recurring_interval')->nullable();
            $table->dateTime('last_generated_at')->nullable();
            $table->dateTime('next_generation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};