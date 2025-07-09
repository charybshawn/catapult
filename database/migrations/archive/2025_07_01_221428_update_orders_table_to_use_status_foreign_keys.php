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
        // Add new foreign key columns
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_status_id')->nullable()->after('status');
            $table->foreignId('payment_status_id')->nullable()->after('payment_status');
            $table->foreignId('delivery_status_id')->nullable()->after('delivery_status');
            $table->foreignId('order_type_id')->nullable()->after('order_type');
            $table->foreignId('order_classification_id')->nullable()->after('order_classification');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE orders 
            SET order_status_id = (
                SELECT id FROM order_statuses 
                WHERE order_statuses.code = orders.status
            )
            WHERE status IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET payment_status_id = (
                SELECT id FROM payment_statuses 
                WHERE payment_statuses.code = orders.payment_status
            )
            WHERE payment_status IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET delivery_status_id = (
                SELECT id FROM delivery_statuses 
                WHERE delivery_statuses.code = orders.delivery_status
            )
            WHERE delivery_status IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET order_type_id = (
                SELECT id FROM order_types 
                WHERE order_types.code = orders.order_type
            )
            WHERE order_type IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET order_classification_id = (
                SELECT id FROM order_classifications 
                WHERE order_classifications.code = orders.order_classification
            )
            WHERE order_classification IS NOT NULL
        ");

        // Add foreign key constraints
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('order_status_id')->references('id')->on('order_statuses');
            $table->foreign('payment_status_id')->references('id')->on('payment_statuses');
            $table->foreign('delivery_status_id')->references('id')->on('delivery_statuses');
            $table->foreign('order_type_id')->references('id')->on('order_types');
            $table->foreign('order_classification_id')->references('id')->on('order_classifications');
        });

        // Drop old enum columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'order_status', 'payment_status', 'delivery_status', 'order_type', 'order_classification']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back enum columns
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'in_production', 'ready_for_harvest', 'harvested', 'packed', 'delivered', 'cancelled', 'draft', 'template'])->default('pending')->after('order_status_id');
            $table->enum('order_status', ['pending', 'confirmed', 'in_production', 'ready_for_harvest', 'harvested', 'packed', 'delivered', 'cancelled'])->default('pending')->after('payment_status_id');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid')->after('delivery_status_id');
            $table->enum('delivery_status', ['pending', 'scheduled', 'in_transit', 'delivered', 'failed'])->default('pending')->after('order_type_id');
            $table->enum('order_type', ['standard', 'subscription', 'b2b'])->default('standard')->after('order_classification_id');
            $table->enum('order_classification', ['scheduled', 'ondemand', 'overflow', 'priority'])->default('scheduled');
        });

        // Copy data back from foreign keys to enums
        DB::statement("
            UPDATE orders 
            SET status = (
                SELECT code FROM order_statuses 
                WHERE order_statuses.id = orders.order_status_id
            )
            WHERE order_status_id IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET payment_status = (
                SELECT code FROM payment_statuses 
                WHERE payment_statuses.id = orders.payment_status_id
            )
            WHERE payment_status_id IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET delivery_status = (
                SELECT code FROM delivery_statuses 
                WHERE delivery_statuses.id = orders.delivery_status_id
            )
            WHERE delivery_status_id IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET order_type = (
                SELECT code FROM order_types 
                WHERE order_types.id = orders.order_type_id
            )
            WHERE order_type_id IS NOT NULL
        ");

        DB::statement("
            UPDATE orders 
            SET order_classification = (
                SELECT code FROM order_classifications 
                WHERE order_classifications.id = orders.order_classification_id
            )
            WHERE order_classification_id IS NOT NULL
        ");

        // Drop foreign keys and columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['order_status_id']);
            $table->dropForeign(['payment_status_id']);
            $table->dropForeign(['delivery_status_id']);
            $table->dropForeign(['order_type_id']);
            $table->dropForeign(['order_classification_id']);
            $table->dropColumn(['order_status_id', 'payment_status_id', 'delivery_status_id', 'order_type_id', 'order_classification_id']);
        });
    }
};
