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
        // First, add the new foreign key column
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')->constrained('payment_statuses')->onDelete('restrict');
        });

        // Map existing enum values to foreign keys
        DB::transaction(function () {
            $statusMapping = [
                'pending' => DB::table('payment_statuses')->where('code', 'pending')->value('id'),
                'completed' => DB::table('payment_statuses')->where('code', 'completed')->value('id'),
                'failed' => DB::table('payment_statuses')->where('code', 'failed')->value('id'),
                'refunded' => DB::table('payment_statuses')->where('code', 'refunded')->value('id'),
            ];

            foreach ($statusMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('payments')
                        ->where('status', $enumValue)
                        ->update(['status_id' => $foreignKeyId]);
                }
            }
        });

        // Make the foreign key column required and drop the enum column
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable(false)->change();
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')->after('payment_method_id');
        });

        // Map foreign keys back to enum values
        DB::transaction(function () {
            $payments = DB::table('payments')
                ->join('payment_statuses', 'payments.status_id', '=', 'payment_statuses.id')
                ->select('payments.id', 'payment_statuses.code as status_code')
                ->get();

            foreach ($payments as $payment) {
                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update(['status' => $payment->status_code]);
            }
        });

        // Drop the foreign key column
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};