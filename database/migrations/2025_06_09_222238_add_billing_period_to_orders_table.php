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
            $table->string('billing_period')->nullable()->after('recurring_end_date')
                ->comment('Billing period for consolidated invoicing (e.g., 2024-01 for monthly, 2024-W15 for weekly)');
            
            // Add index for better query performance
            $table->index(['billing_period', 'order_type'], 'orders_billing_period_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_billing_period_type_index');
            $table->dropColumn('billing_period');
        });
    }
};