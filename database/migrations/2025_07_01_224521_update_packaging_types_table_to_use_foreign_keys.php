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
        // First, add the new foreign key columns
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->foreignId('type_category_id')->nullable()->after('name')->constrained('packaging_type_categories')->onDelete('restrict');
            $table->foreignId('unit_type_id')->nullable()->after('type_category_id')->constrained('packaging_unit_types')->onDelete('restrict');
        });

        // Map existing enum values to foreign keys
        DB::transaction(function () {
            // Map packaging type categories
            $categoryMapping = [
                'clamshell' => DB::table('packaging_type_categories')->where('code', 'clamshell')->value('id'),
                'bag' => DB::table('packaging_type_categories')->where('code', 'bag')->value('id'),
                'box' => DB::table('packaging_type_categories')->where('code', 'box')->value('id'),
                'jar' => DB::table('packaging_type_categories')->where('code', 'jar')->value('id'),
                'tray' => DB::table('packaging_type_categories')->where('code', 'tray')->value('id'),
                'bulk' => DB::table('packaging_type_categories')->where('code', 'bulk')->value('id'),
                'other' => DB::table('packaging_type_categories')->where('code', 'other')->value('id'),
            ];

            // Map packaging unit types
            $unitTypeMapping = [
                'count' => DB::table('packaging_unit_types')->where('code', 'count')->value('id'),
                'weight' => DB::table('packaging_unit_types')->where('code', 'weight')->value('id'),
            ];

            // Update packaging type categories
            foreach ($categoryMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('packaging_types')
                        ->where('type', $enumValue)
                        ->update(['type_category_id' => $foreignKeyId]);
                }
            }

            // Update packaging unit types
            foreach ($unitTypeMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('packaging_types')
                        ->where('unit_type', $enumValue)
                        ->update(['unit_type_id' => $foreignKeyId]);
                }
            }
        });

        // Make the foreign key columns required and drop the enum columns
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->foreignId('type_category_id')->nullable(false)->change();
            $table->foreignId('unit_type_id')->nullable(false)->change();
            $table->dropColumn(['type', 'unit_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum columns
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->enum('type', ['clamshell', 'bag', 'box', 'jar', 'tray', 'bulk', 'other'])->default('other')->after('name');
            $table->enum('unit_type', ['count', 'weight'])->default('count')->after('type');
        });

        // Map foreign keys back to enum values
        DB::transaction(function () {
            // Map packaging type categories back
            $packagingTypes = DB::table('packaging_types')
                ->join('packaging_type_categories', 'packaging_types.type_category_id', '=', 'packaging_type_categories.id')
                ->select('packaging_types.id', 'packaging_type_categories.code as category_code')
                ->get();

            foreach ($packagingTypes as $packagingType) {
                DB::table('packaging_types')
                    ->where('id', $packagingType->id)
                    ->update(['type' => $packagingType->category_code]);
            }

            // Map packaging unit types back
            $packagingTypes = DB::table('packaging_types')
                ->join('packaging_unit_types', 'packaging_types.unit_type_id', '=', 'packaging_unit_types.id')
                ->select('packaging_types.id', 'packaging_unit_types.code as unit_type_code')
                ->get();

            foreach ($packagingTypes as $packagingType) {
                DB::table('packaging_types')
                    ->where('id', $packagingType->id)
                    ->update(['unit_type' => $packagingType->unit_type_code]);
            }
        });

        // Drop the foreign key columns
        Schema::table('packaging_types', function (Blueprint $table) {
            $table->dropForeign(['type_category_id']);
            $table->dropForeign(['unit_type_id']);
            $table->dropColumn(['type_category_id', 'unit_type_id']);
        });
    }
};
