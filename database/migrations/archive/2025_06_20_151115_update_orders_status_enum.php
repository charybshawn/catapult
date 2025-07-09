<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the status enum to include missing values
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'confirmed', 'in_production', 'ready_for_harvest', 'harvested', 'packed', 'delivered', 'cancelled', 'draft', 'template') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'confirmed', 'in_production', 'ready_for_harvest', 'harvested', 'packed', 'delivered', 'cancelled') DEFAULT 'pending'");
    }
};
