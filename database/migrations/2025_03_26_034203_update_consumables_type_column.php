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
        // First, determine what type of column we're dealing with
        $columns = DB::select('SHOW COLUMNS FROM consumables WHERE Field = "type"');
        $columnType = $columns[0]->Type ?? null;
        
        // If it's an enum, convert to varchar
        if (strpos($columnType, 'enum') === 0) {
            Schema::table('consumables', function (Blueprint $table) {
                $table->string('type', 20)->change();
            });
        } 
        // If it's already a string but too short, expand it
        else if (strpos($columnType, 'varchar') === 0 || strpos($columnType, 'char') === 0) {
            Schema::table('consumables', function (Blueprint $table) {
                $table->string('type', 20)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't restore the original constraints since it might cause data loss
        // if there are already entries with new types
    }
};
