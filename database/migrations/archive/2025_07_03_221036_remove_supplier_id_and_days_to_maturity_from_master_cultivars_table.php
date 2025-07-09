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
        Schema::table('master_cultivars', function (Blueprint $table) {
            $table->dropColumn('days_to_maturity');
            $table->json('aliases')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_cultivars', function (Blueprint $table) {
            $table->dropColumn('aliases');
            $table->integer('days_to_maturity')->nullable();
        });
    }
};
