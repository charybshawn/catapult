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
        // Skip legacy migration since we're starting with a clean implementation
        // The image column doesn't exist, so we don't need to migrate anything
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to revert
    }
};
