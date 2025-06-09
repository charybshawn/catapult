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
        Schema::table('consumables', function (Blueprint $table) {
            $table->unsignedBigInteger('seed_entry_id')->nullable()->after('packaging_type_id');
            $table->foreign('seed_entry_id')->references('id')->on('seed_entries')->onDelete('set null');
            $table->index('seed_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['seed_entry_id']);
            $table->dropIndex(['seed_entry_id']);
            $table->dropColumn('seed_entry_id');
        });
    }
};
