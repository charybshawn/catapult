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
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['order_id']);
        });
        
        Schema::table('invoices', function (Blueprint $table) {
            // Re-add the foreign key with CASCADE on delete
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['order_id']);
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Re-add the foreign key with CASCADE on delete
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the CASCADE foreign key
            $table->dropForeign(['order_id']);
        });
        
        Schema::table('invoices', function (Blueprint $table) {
            // Restore the RESTRICT foreign key
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Drop the CASCADE foreign key
            $table->dropForeign(['order_id']);
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Restore the RESTRICT foreign key
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
        });
    }
};
