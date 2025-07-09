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
        Schema::table('task_schedules', function (Blueprint $table) {
            $table->string('resource_type')->nullable()->after('id');
            $table->string('task_name')->nullable()->after('resource_type');
            $table->string('time_of_day')->nullable()->after('schedule_config');
            $table->integer('day_of_week')->nullable()->after('time_of_day');
            $table->integer('day_of_month')->nullable()->after('day_of_week');
            $table->json('conditions')->nullable()->after('day_of_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_schedules', function (Blueprint $table) {
            $table->dropColumn(['resource_type', 'task_name', 'time_of_day', 'day_of_week', 'day_of_month', 'conditions']);
        });
    }
};
