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
        // First, check for any duplicate names before adding the unique constraint
        $duplicates = DB::table('products')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();
        
        if ($duplicates->count() > 0) {
            // If there are duplicates, we need to make them unique first
            foreach ($duplicates as $duplicate) {
                $products = DB::table('products')
                    ->where('name', $duplicate->name)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->get();
                
                // Skip the first one, rename the rest
                $counter = 1;
                foreach ($products->skip(1) as $product) {
                    $newName = $duplicate->name . ' (' . $counter . ')';
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['name' => $newName]);
                    $counter++;
                }
            }
        }
        
        // Now add the unique index
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['name', 'deleted_at'], 'products_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_name_unique');
        });
    }
};
