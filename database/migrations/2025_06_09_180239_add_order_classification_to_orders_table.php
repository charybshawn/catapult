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
            // Add order classification fields
            $table->enum('order_type', ['farmers_market', 'b2b_recurring', 'website_immediate'])
                ->default('website_immediate')
                ->after('customer_type');
            
            $table->enum('billing_frequency', ['immediate', 'weekly', 'monthly', 'quarterly'])
                ->default('immediate')
                ->after('order_type');
                
            $table->boolean('requires_invoice')
                ->default(true)
                ->after('billing_frequency');
                
            $table->date('billing_period_start')
                ->nullable()
                ->after('requires_invoice');
                
            $table->date('billing_period_end')
                ->nullable()
                ->after('billing_period_start');
                
            $table->unsignedBigInteger('consolidated_invoice_id')
                ->nullable()
                ->after('billing_period_end');
                
            $table->json('billing_preferences')
                ->nullable()
                ->after('consolidated_invoice_id');
                
            // Add foreign key for consolidated invoices
            $table->foreign('consolidated_invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['consolidated_invoice_id']);
            $table->dropColumn([
                'order_type',
                'billing_frequency', 
                'requires_invoice',
                'billing_period_start',
                'billing_period_end',
                'consolidated_invoice_id',
                'billing_preferences'
            ]);
        });
    }
};