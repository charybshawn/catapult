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
        // Add new foreign key column
        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->foreignId('recipe_stage_type_id')->nullable()->after('stage');
        });

        // Map existing enum values to foreign keys
        DB::statement("
            UPDATE recipe_stages 
            SET recipe_stage_type_id = (
                SELECT id FROM recipe_stage_types 
                WHERE recipe_stage_types.code = recipe_stages.stage
            )
            WHERE stage IS NOT NULL
        ");

        // Make the foreign key non-nullable and add constraint
        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->foreignId('recipe_stage_type_id')->nullable(false)->change();
            $table->foreign('recipe_stage_type_id')->references('id')->on('recipe_stage_types');
        });

        // Drop old enum column
        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back enum column
        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->enum('stage', ['germination', 'blackout', 'light'])->after('recipe_stage_type_id');
        });

        // Copy data back from foreign keys to enum
        DB::statement("
            UPDATE recipe_stages 
            SET stage = (
                SELECT code FROM recipe_stage_types 
                WHERE recipe_stage_types.id = recipe_stages.recipe_stage_type_id
            )
            WHERE recipe_stage_type_id IS NOT NULL
        ");

        // Drop foreign key and column
        Schema::table('recipe_stages', function (Blueprint $table) {
            $table->dropForeign(['recipe_stage_type_id']);
            $table->dropColumn('recipe_stage_type_id');
        });
    }
};