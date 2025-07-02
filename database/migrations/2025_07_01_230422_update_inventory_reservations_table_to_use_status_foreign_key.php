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
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')->constrained('inventory_reservation_statuses')->onDelete('restrict');
        });

        // Map existing enum values to foreign keys
        DB::transaction(function () {
            $statusMapping = [
                'pending' => DB::table('inventory_reservation_statuses')->where('code', 'pending')->value('id'),
                'confirmed' => DB::table('inventory_reservation_statuses')->where('code', 'confirmed')->value('id'),
                'fulfilled' => DB::table('inventory_reservation_statuses')->where('code', 'fulfilled')->value('id'),
                'cancelled' => DB::table('inventory_reservation_statuses')->where('code', 'cancelled')->value('id'),
            ];

            foreach ($statusMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('inventory_reservations')
                        ->where('status', $enumValue)
                        ->update(['status_id' => $foreignKeyId]);
                }
            }
        });

        // Make the foreign key column required and drop the enum column
        Schema::table('inventory_reservations', function (Blueprint $table) {
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
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'fulfilled', 'cancelled'])->default('pending')->after('reserved_until');
        });

        // Map foreign keys back to enum values
        DB::transaction(function () {
            $reservations = DB::table('inventory_reservations')
                ->join('inventory_reservation_statuses', 'inventory_reservations.status_id', '=', 'inventory_reservation_statuses.id')
                ->select('inventory_reservations.id', 'inventory_reservation_statuses.code as status_code')
                ->get();

            foreach ($reservations as $reservation) {
                DB::table('inventory_reservations')
                    ->where('id', $reservation->id)
                    ->update(['status' => $reservation->status_code]);
            }
        });

        // Drop the foreign key column
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};