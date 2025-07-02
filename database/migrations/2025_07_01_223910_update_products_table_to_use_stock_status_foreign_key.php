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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('stock_status_id')->nullable()->after('track_inventory')->constrained('product_stock_statuses')->onDelete('restrict');
        });

        // Map existing enum values to foreign keys
        DB::transaction(function () {
            $statusMapping = [
                'in_stock' => DB::table('product_stock_statuses')->where('code', 'in_stock')->value('id'),
                'low_stock' => DB::table('product_stock_statuses')->where('code', 'low_stock')->value('id'),
                'out_of_stock' => DB::table('product_stock_statuses')->where('code', 'out_of_stock')->value('id'),
                'discontinued' => DB::table('product_stock_statuses')->where('code', 'discontinued')->value('id'),
            ];

            foreach ($statusMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('products')
                        ->where('stock_status', $enumValue)
                        ->update(['stock_status_id' => $foreignKeyId]);
                }
            }
        });

        // Make the foreign key column required and drop the enum column
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('stock_status_id')->nullable(false)->change();
            $table->dropColumn('stock_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('products', function (Blueprint $table) {
            $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock', 'discontinued'])->default('in_stock')->after('track_inventory');
        });

        // Map foreign keys back to enum values
        DB::transaction(function () {
            $products = DB::table('products')
                ->join('product_stock_statuses', 'products.stock_status_id', '=', 'product_stock_statuses.id')
                ->select('products.id', 'product_stock_statuses.code as status_code')
                ->get();

            foreach ($products as $product) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['stock_status' => $product->status_code]);
            }
        });

        // Drop the foreign key column
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['stock_status_id']);
            $table->dropColumn('stock_status_id');
        });
    }
};
