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
        Schema::table('crops', function (Blueprint $table) {
            if (!Schema::hasColumn('crops', 'stage_updated_at')) {
                $table->timestamp('stage_updated_at')->nullable()->after('current_stage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            if (Schema::hasColumn('crops', 'stage_updated_at')) {
                $table->dropColumn('stage_updated_at');
            }
        });
    }
};