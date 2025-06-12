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
        Schema::table('products', function (Blueprint $table) {
            // Drop the existing seed_entry_id foreign key and column
            $table->dropForeign(['seed_entry_id']);
            $table->dropIndex(['seed_entry_id', 'active']); // Drop compound index
            $table->dropColumn('seed_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore seed_entry_id column
            $table->unsignedBigInteger('seed_entry_id')->nullable()->after('product_mix_id');
            $table->foreign('seed_entry_id')->references('id')->on('seed_entries')->onDelete('set null');
            $table->index(['seed_entry_id', 'active']);
        });
    }
};
