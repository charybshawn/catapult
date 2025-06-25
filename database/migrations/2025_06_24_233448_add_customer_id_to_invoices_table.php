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
        Schema::table('invoices', function (Blueprint $table) {
            // Add customer_id column
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            
            // Add index for performance
            $table->index('customer_id');
        });
        
        // Update invoices to point to customer records through orders
        DB::statement('
            UPDATE invoices i
            INNER JOIN orders o ON i.order_id = o.id
            SET i.customer_id = o.customer_id
            WHERE i.customer_id IS NULL AND o.customer_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};