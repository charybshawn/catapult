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
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->decimal('capacity_volume', 8, 2)->after('capacity_grams')->default(0);
            $table->string('volume_unit', 20)->after('capacity_volume')->default('oz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->dropColumn('capacity_volume');
            $table->dropColumn('volume_unit');
        });
    }
};
