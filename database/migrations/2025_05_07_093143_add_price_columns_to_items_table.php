<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('base_price', 8, 2)->nullable();
            $table->decimal('wholesale_price', 8, 2)->nullable();
            $table->decimal('bulk_price', 8, 2)->nullable();
            $table->decimal('special_price', 8, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'base_price',
                'wholesale_price',
                'bulk_price',
                'special_price',
            ]);
        });
    }
}; 