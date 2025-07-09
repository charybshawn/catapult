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
            // Add foreign key columns
            $table->foreignId('crop_status_id')->nullable()->after('order_status_id')->constrained('crop_statuses');
            $table->foreignId('fulfillment_status_id')->nullable()->after('crop_status_id')->constrained('fulfillment_statuses');
        });

        // Migrate existing data from string columns to foreign keys
        $this->migrateExistingData();

        Schema::table('orders', function (Blueprint $table) {
            // Remove old string columns
            $table->dropColumn(['crop_status', 'fulfillment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add back the string columns
            $table->string('crop_status')->nullable()->after('order_status_id');
            $table->string('fulfillment_status')->nullable()->after('crop_status');
        });

        // Migrate data back from foreign keys to string columns
        $this->migrateDataBack();

        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key columns
            $table->dropForeign(['crop_status_id']);
            $table->dropForeign(['fulfillment_status_id']);
            $table->dropColumn(['crop_status_id', 'fulfillment_status_id']);
        });
    }

    private function migrateExistingData(): void
    {
        $orders = DB::table('orders')->get();

        foreach ($orders as $order) {
            $updates = [];

            // Map crop_status to crop_status_id
            if ($order->crop_status) {
                $cropStatus = DB::table('crop_statuses')->where('code', $order->crop_status)->first();
                if ($cropStatus) {
                    $updates['crop_status_id'] = $cropStatus->id;
                }
            }

            // Map fulfillment_status to fulfillment_status_id
            if ($order->fulfillment_status) {
                $fulfillmentStatus = DB::table('fulfillment_statuses')->where('code', $order->fulfillment_status)->first();
                if ($fulfillmentStatus) {
                    $updates['fulfillment_status_id'] = $fulfillmentStatus->id;
                }
            }

            if (!empty($updates)) {
                DB::table('orders')->where('id', $order->id)->update($updates);
            }
        }
    }

    private function migrateDataBack(): void
    {
        $orders = DB::table('orders')
            ->leftJoin('crop_statuses', 'orders.crop_status_id', '=', 'crop_statuses.id')
            ->leftJoin('fulfillment_statuses', 'orders.fulfillment_status_id', '=', 'fulfillment_statuses.id')
            ->select('orders.id', 'crop_statuses.code as crop_code', 'fulfillment_statuses.code as fulfillment_code')
            ->get();

        foreach ($orders as $order) {
            $updates = [];

            if ($order->crop_code) {
                $updates['crop_status'] = $order->crop_code;
            }

            if ($order->fulfillment_code) {
                $updates['fulfillment_status'] = $order->fulfillment_code;
            }

            if (!empty($updates)) {
                DB::table('orders')->where('id', $order->id)->update($updates);
            }
        }
    }
};