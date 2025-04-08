<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: This migration doesn't alter the database structure as the
     * 'code' column doesn't exist in the database, but is referenced in the model.
     */
    public function up(): void
    {
        // The code column doesn't actually exist in the database
        // This migration exists for documentation purposes
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to reverse
    }
};
