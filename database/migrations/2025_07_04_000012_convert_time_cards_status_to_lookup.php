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
        Schema::table('time_cards', function (Blueprint $table) {
            $table->foreignId('time_card_status_id')->nullable()->after('status');
        });

        // Map existing string values to foreign keys
        // First, let's check what values exist in the status column
        $existingStatuses = DB::table('time_cards')
            ->whereNotNull('status')
            ->distinct()
            ->pluck('status');

        // Map common status strings to our standardized codes
        $statusMap = [
            'draft' => 'draft',
            'submitted' => 'submitted',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'paid' => 'paid',
            // Add any other mappings as needed
        ];

        foreach ($existingStatuses as $status) {
            $code = $statusMap[strtolower($status)] ?? 'draft';
            DB::statement("
                UPDATE time_cards 
                SET time_card_status_id = (
                    SELECT id FROM time_card_statuses 
                    WHERE time_card_statuses.code = ?
                )
                WHERE LOWER(status) = ?
            ", [$code, strtolower($status)]);
        }

        // Set default for null values
        DB::statement("
            UPDATE time_cards 
            SET time_card_status_id = (
                SELECT id FROM time_card_statuses 
                WHERE time_card_statuses.code = 'draft'
            )
            WHERE time_card_status_id IS NULL
        ");

        // Make the foreign key non-nullable and add constraint
        Schema::table('time_cards', function (Blueprint $table) {
            $table->foreignId('time_card_status_id')->nullable(false)->change();
            $table->foreign('time_card_status_id')->references('id')->on('time_card_statuses');
        });

        // Drop old string column
        Schema::table('time_cards', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back string column
        Schema::table('time_cards', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('time_card_status_id');
        });

        // Copy data back from foreign keys to string
        DB::statement("
            UPDATE time_cards 
            SET status = (
                SELECT code FROM time_card_statuses 
                WHERE time_card_statuses.id = time_cards.time_card_status_id
            )
            WHERE time_card_status_id IS NOT NULL
        ");

        // Drop foreign key and column
        Schema::table('time_cards', function (Blueprint $table) {
            $table->dropForeign(['time_card_status_id']);
            $table->dropColumn('time_card_status_id');
        });
    }
};