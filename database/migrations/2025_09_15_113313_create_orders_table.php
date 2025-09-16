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
                    $table->id('id');
                    $table->bigInteger('user_id');
                    $table->bigInteger('customer_id')->nullable();
                    $table->string('harvest_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('delivery_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('harvest_day', 50)->nullable();
                    $table->string('delivery_day', 50)->nullable();
                    $table->integer('start_delay_weeks');
                    $table->bigInteger('status_id')->nullable();
                    $table->bigInteger('crop_status_id')->nullable();
                    $table->bigInteger('fulfillment_status_id')->nullable();
                    $table->bigInteger('payment_status_id')->nullable();
                    $table->bigInteger('delivery_status_id')->nullable();
                    $table->string('customer_type', 50);
                    $table->bigInteger('order_type_id')->nullable();
                    $table->string('billing_frequency', 255)->nullable();
                    $table->integer('requires_invoice');
                    $table->string('billing_period_start')->nullable(); // TODO: Review type for: date default null
                    $table->string('billing_period_end')->nullable(); // TODO: Review type for: date default null
                    $table->bigInteger('consolidated_invoice_id')->nullable();
                    $table->json('billing_preferences')->nullable();
                    $table->bigInteger('order_classification_id')->nullable();
                    $table->string('billing_period', 255)->nullable();
                    $table->integer('is_recurring');
                    $table->bigInteger('parent_recurring_order_id')->nullable();
                    $table->string('recurring_frequency', 255)->nullable();
                    $table->string('recurring_start_date')->nullable(); // TODO: Review type for: date default null
                    $table->string('recurring_end_date')->nullable(); // TODO: Review type for: date default null
                    $table->integer('is_recurring_active');
                    $table->json('recurring_days_of_week')->nullable();
                    $table->integer('recurring_interval')->nullable();
                    $table->timestamp('last_generated_at')->nullable();
                    $table->timestamp('next_generation_date')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
