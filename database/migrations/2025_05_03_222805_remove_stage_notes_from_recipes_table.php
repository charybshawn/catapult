<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB for raw SQL in down method if needed

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'planting_notes',
                'germination_notes',
                'blackout_notes',
                'light_notes',
                'harvesting_notes'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Add the columns back. Assuming they were text and nullable.
            // Place them back relative to 'notes' or another existing column if possible.
            $table->text('planting_notes')->nullable()->after('notes');
            $table->text('germination_notes')->nullable()->after('planting_notes');
            $table->text('blackout_notes')->nullable()->after('germination_notes');
            $table->text('light_notes')->nullable()->after('blackout_notes');
            $table->text('harvesting_notes')->nullable()->after('light_notes');
        });
    }
};
