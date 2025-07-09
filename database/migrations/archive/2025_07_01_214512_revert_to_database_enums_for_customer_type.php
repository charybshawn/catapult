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
        // Convert back to enum with farmers_market included
        DB::statement("ALTER TABLE customers MODIFY COLUMN customer_type ENUM('retail', 'wholesale', 'farmers_market') NOT NULL DEFAULT 'retail'");
        DB::statement("ALTER TABLE users MODIFY COLUMN customer_type ENUM('retail', 'wholesale', 'farmers_market') NOT NULL DEFAULT 'retail'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to varchar
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 50)->default('retail')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_type', 50)->default('retail')->change();
        });
    }
};
