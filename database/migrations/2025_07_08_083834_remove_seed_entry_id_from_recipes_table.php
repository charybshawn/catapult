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
        Schema::table('recipes', function (Blueprint $table) {
            // Drop foreign key constraint if it exists
            if (Schema::hasColumn('recipes', 'seed_entry_id')) {
                $table->dropForeign(['seed_entry_id']);
                $table->dropColumn('seed_entry_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->unsignedBigInteger('seed_entry_id')->nullable()->after('name');
            $table->foreign('seed_entry_id')->references('id')->on('seed_entries')->onDelete('set null');
        });
    }
};
