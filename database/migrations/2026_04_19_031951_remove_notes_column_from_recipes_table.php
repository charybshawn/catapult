<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTE: This migration is intentionally empty and will not run.
     * We've decided to keep the notes column in the recipes table for storing growth phase notes.
     * This file is kept as a placeholder to document the decision and avoid migration number conflicts.
     */
    public function up(): void
    {
        // No action - keeping notes column in recipes table
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed
    }
};
