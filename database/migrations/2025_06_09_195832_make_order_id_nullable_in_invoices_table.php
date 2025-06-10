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
            // Drop foreign key constraint first
            $table->dropForeign(['order_id']);
            
            // Make order_id nullable
            $table->unsignedBigInteger('order_id')->nullable()->change();
            
            // Re-add foreign key constraint with nullable support
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['order_id']);
            
            // Make order_id not nullable again
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            
            // Re-add foreign key constraint
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });
    }
};
