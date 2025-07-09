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
            $table->string('lot_number')->nullable()->after('seed_consumable_id');
            $table->timestamp('lot_depleted_at')->nullable()->after('lot_number');
            $table->index(['lot_number', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['lot_number', 'is_active']);
            $table->dropColumn(['lot_number', 'lot_depleted_at']);
        });
    }
};
