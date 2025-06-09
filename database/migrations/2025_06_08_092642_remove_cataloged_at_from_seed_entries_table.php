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
        Schema::table('seed_entries', function (Blueprint $table) {
            $table->dropColumn('cataloged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_entries', function (Blueprint $table) {
            $table->timestamp('cataloged_at')->nullable()->after('tags');
        });
    }
};
