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
        Schema::table('seed_variations', function (Blueprint $table) {
            $table->foreignId('consumable_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_variations', function (Blueprint $table) {
            $table->dropForeign(['consumable_id']);
            $table->dropColumn('consumable_id');
        });
    }
};
