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
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->string('cultivar')->nullable()->after('variety_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->dropColumn('cultivar');
        });
    }
};