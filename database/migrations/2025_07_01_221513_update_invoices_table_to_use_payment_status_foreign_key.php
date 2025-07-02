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
        // Add new foreign key column
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('payment_status_id')->nullable()->after('status');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE invoices 
            SET payment_status_id = (
                SELECT id FROM payment_statuses 
                WHERE payment_statuses.code = invoices.status
            )
            WHERE status IS NOT NULL
        ");

        // Add foreign key constraint
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('payment_status_id')->references('id')->on('payment_statuses');
        });

        // Drop old enum column
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back enum column
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft')->after('payment_status_id');
        });

        // Copy data back from foreign key to enum
        DB::statement("
            UPDATE invoices 
            SET status = (
                SELECT code FROM payment_statuses 
                WHERE payment_statuses.id = invoices.payment_status_id
            )
            WHERE payment_status_id IS NOT NULL
        ");

        // Drop foreign key and column
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['payment_status_id']);
            $table->dropColumn('payment_status_id');
        });
    }
};
