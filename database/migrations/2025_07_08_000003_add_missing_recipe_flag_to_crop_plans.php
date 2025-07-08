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
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->boolean('is_missing_recipe')->default(false)->after('admin_notes');
            $table->string('missing_recipe_notes')->nullable()->after('is_missing_recipe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->dropColumn(['is_missing_recipe', 'missing_recipe_notes']);
        });
    }
};