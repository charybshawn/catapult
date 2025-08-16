<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the MySQL view as we're replacing it with service-based architecture
        DB::statement('DROP VIEW IF EXISTS crop_batches_list_view');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We intentionally don't recreate the view in down() since it was problematic
        // If needed, the old migration files can be referenced for the view structure
    }
};
