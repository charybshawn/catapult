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
        Schema::table('orders', function (Blueprint $table) {
            // Add customer_id column
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            
            // Add index for performance
            $table->index('customer_id');
        });
        
        // Migrate existing data: Create customer records for existing orders
        // This will create a customer record for each unique user that has orders
        DB::statement('
            INSERT INTO customers (contact_name, email, phone, customer_type, address, city, province, postal_code, country, user_id, created_at, updated_at)
            SELECT DISTINCT 
                u.name as contact_name,
                u.email,
                u.phone,
                COALESCE(u.customer_type, "retail") as customer_type,
                u.address,
                u.city,
                u.state as province,
                u.zip as postal_code,
                "CA" as country,
                u.id as user_id,
                u.created_at,
                u.updated_at
            FROM users u
            INNER JOIN orders o ON o.user_id = u.id
            WHERE u.id NOT IN (SELECT user_id FROM customers WHERE user_id IS NOT NULL)
        ');
        
        // Update orders to point to the new customer records
        DB::statement('
            UPDATE orders o
            INNER JOIN customers c ON c.user_id = o.user_id
            SET o.customer_id = c.id
            WHERE o.customer_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};