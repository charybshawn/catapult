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
        Schema::table('suppliers', function (Blueprint $table) {
            // Modify the enum to include 'packaging' type
            $table->enum('type', ['soil', 'seed', 'consumable', 'other', 'packaging'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Revert back to previous enum values
            $table->enum('type', ['soil', 'seed', 'consumable', 'other'])->change();
        });
    }
};
