<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_variations', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('is_default')
                  ->comment('When true, this variation can be used with any product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_variations', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};
