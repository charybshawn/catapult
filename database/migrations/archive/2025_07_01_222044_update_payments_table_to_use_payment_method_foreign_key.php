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
        // Add the new foreign key column
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('method');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE payments 
            SET payment_method_id = (
                SELECT id FROM payment_methods 
                WHERE payment_methods.code = payments.method
            )
            WHERE method IS NOT NULL
        ");

        // Add foreign key constraint
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');
        });

        // Drop the old enum column
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['stripe', 'e-transfer', 'cash', 'invoice'])->default('cash')->after('payment_method_id');
        });

        // Copy data back from foreign key to enum
        DB::statement("
            UPDATE payments 
            SET method = (
                SELECT code FROM payment_methods 
                WHERE payment_methods.id = payments.payment_method_id
            )
            WHERE payment_method_id IS NOT NULL
        ");

        // Drop foreign key and column
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
