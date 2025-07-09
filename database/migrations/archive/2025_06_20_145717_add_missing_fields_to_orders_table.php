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
        Schema::table('orders', function (Blueprint $table) {
            // Add missing status fields (status, customer_type, order_type, billing_period already exist)
            $table->string('crop_status')->nullable()->after('status');
            $table->string('fulfillment_status')->nullable()->after('crop_status');
            
            // Add billing fields
            $table->string('billing_frequency')->nullable()->after('order_type');
            $table->boolean('requires_invoice')->default(false)->after('billing_frequency');
            $table->date('billing_period_start')->nullable()->after('requires_invoice');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
            $table->unsignedBigInteger('consolidated_invoice_id')->nullable()->after('billing_period_end');
            $table->json('billing_preferences')->nullable()->after('consolidated_invoice_id');
            
            // Add recurring order fields
            $table->boolean('is_recurring')->default(false)->after('billing_period');
            $table->unsignedBigInteger('parent_recurring_order_id')->nullable()->after('is_recurring');
            $table->string('recurring_frequency')->nullable()->after('parent_recurring_order_id');
            $table->date('recurring_start_date')->nullable()->after('recurring_frequency');
            $table->date('recurring_end_date')->nullable()->after('recurring_start_date');
            $table->boolean('is_recurring_active')->default(true)->after('recurring_end_date');
            $table->json('recurring_days_of_week')->nullable()->after('is_recurring_active');
            $table->integer('recurring_interval')->nullable()->after('recurring_days_of_week');
            $table->datetime('last_generated_at')->nullable()->after('recurring_interval');
            $table->datetime('next_generation_date')->nullable()->after('last_generated_at');
            
            // Add foreign key constraints
            $table->foreign('consolidated_invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('parent_recurring_order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['consolidated_invoice_id']);
            $table->dropForeign(['parent_recurring_order_id']);
            
            // Drop added columns
            $table->dropColumn([
                'crop_status', 'fulfillment_status',
                'billing_frequency', 'requires_invoice', 'billing_period_start', 'billing_period_end',
                'consolidated_invoice_id', 'billing_preferences',
                'is_recurring', 'parent_recurring_order_id', 'recurring_frequency',
                'recurring_start_date', 'recurring_end_date', 'is_recurring_active',
                'recurring_days_of_week', 'recurring_interval', 'last_generated_at', 'next_generation_date'
            ]);
        });
    }
};
