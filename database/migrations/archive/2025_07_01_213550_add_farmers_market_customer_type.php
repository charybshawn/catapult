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
        // First, clean up any NULL or invalid values
        DB::statement("UPDATE customers SET customer_type = 'retail' WHERE customer_type IS NULL OR customer_type = ''");
        DB::statement("UPDATE users SET customer_type = 'retail' WHERE customer_type IS NULL OR customer_type = ''");
        
        // Convert enum columns to varchar to allow Laravel-based validation
        DB::statement("ALTER TABLE customers MODIFY COLUMN customer_type VARCHAR(50) NOT NULL DEFAULT 'retail'");
        DB::statement("ALTER TABLE users MODIFY COLUMN customer_type VARCHAR(50) NOT NULL DEFAULT 'retail'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'wholesale'])->default('retail')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'wholesale'])->default('retail')->change();
        });
    }
};
