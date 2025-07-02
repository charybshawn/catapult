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
        // First, add the new foreign key column
        Schema::table('crops', function (Blueprint $table) {
            $table->foreignId('current_stage_id')->nullable()->after('current_stage')->constrained('crop_stages')->onDelete('restrict');
        });

        // Map existing enum values to foreign keys
        DB::transaction(function () {
            $stageMapping = [
                'germination' => DB::table('crop_stages')->where('code', 'germination')->value('id'),
                'blackout' => DB::table('crop_stages')->where('code', 'blackout')->value('id'),
                'light' => DB::table('crop_stages')->where('code', 'light')->value('id'),
                'harvested' => DB::table('crop_stages')->where('code', 'harvested')->value('id'),
            ];

            foreach ($stageMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('crops')
                        ->where('current_stage', $enumValue)
                        ->update(['current_stage_id' => $foreignKeyId]);
                }
            }
        });

        // Make the foreign key column required and drop the enum column
        Schema::table('crops', function (Blueprint $table) {
            $table->foreignId('current_stage_id')->nullable(false)->change();
            $table->dropColumn('current_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('crops', function (Blueprint $table) {
            $table->enum('current_stage', ['germination', 'blackout', 'light', 'harvested'])->default('germination')->after('crop_plan_id');
        });

        // Map foreign keys back to enum values
        DB::transaction(function () {
            $crops = DB::table('crops')
                ->join('crop_stages', 'crops.current_stage_id', '=', 'crop_stages.id')
                ->select('crops.id', 'crop_stages.code as stage_code')
                ->get();

            foreach ($crops as $crop) {
                DB::table('crops')
                    ->where('id', $crop->id)
                    ->update(['current_stage' => $crop->stage_code]);
            }
        });

        // Drop the foreign key column
        Schema::table('crops', function (Blueprint $table) {
            $table->dropForeign(['current_stage_id']);
            $table->dropColumn('current_stage_id');
        });
    }
};
