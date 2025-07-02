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
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('notes')->constrained('crop_plan_statuses')->onDelete('restrict');
        });

        // Map existing enum values to foreign keys
        DB::transaction(function () {
            $statusMapping = [
                'draft' => DB::table('crop_plan_statuses')->where('code', 'draft')->value('id'),
                'active' => DB::table('crop_plan_statuses')->where('code', 'active')->value('id'),
                'completed' => DB::table('crop_plan_statuses')->where('code', 'completed')->value('id'),
                'cancelled' => DB::table('crop_plan_statuses')->where('code', 'cancelled')->value('id'),
            ];

            foreach ($statusMapping as $enumValue => $foreignKeyId) {
                if ($foreignKeyId) {
                    DB::table('crop_plans')
                        ->where('status', $enumValue)
                        ->update(['status_id' => $foreignKeyId]);
                }
            }
        });

        // Make the foreign key column required and drop the enum column
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable(false)->change();
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the enum column
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->after('notes');
        });

        // Map foreign keys back to enum values
        DB::transaction(function () {
            $cropPlans = DB::table('crop_plans')
                ->join('crop_plan_statuses', 'crop_plans.status_id', '=', 'crop_plan_statuses.id')
                ->select('crop_plans.id', 'crop_plan_statuses.code as status_code')
                ->get();

            foreach ($cropPlans as $cropPlan) {
                DB::table('crop_plans')
                    ->where('id', $cropPlan->id)
                    ->update(['status' => $cropPlan->status_code]);
            }
        });

        // Drop the foreign key column
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
