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
        Schema::table('inventory', function (Blueprint $table) {
            // Add the new foreign key to seed_varieties
            $table->foreignId('seed_variety_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Drop the new foreign key
            $table->dropForeign(['seed_variety_id']);
            $table->dropColumn('seed_variety_id');
        });
    }
};
