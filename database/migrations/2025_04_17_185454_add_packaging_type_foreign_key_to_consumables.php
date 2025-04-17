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
            // Add foreign key constraint for packaging_type_id
            $table->foreign('packaging_type_id')
                ->references('id')
                ->on('packaging_types')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['packaging_type_id']);
        });
    }
};
