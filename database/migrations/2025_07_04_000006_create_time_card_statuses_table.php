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
        Schema::create('time_card_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 20)->default('gray');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // Insert default statuses
        DB::table('time_card_statuses')->insert([
            ['code' => 'draft', 'name' => 'Draft', 'description' => 'Time card is being prepared', 'color' => 'gray', 'sort_order' => 1],
            ['code' => 'submitted', 'name' => 'Submitted', 'description' => 'Time card has been submitted for approval', 'color' => 'blue', 'sort_order' => 2],
            ['code' => 'approved', 'name' => 'Approved', 'description' => 'Time card has been approved', 'color' => 'green', 'sort_order' => 3],
            ['code' => 'rejected', 'name' => 'Rejected', 'description' => 'Time card has been rejected', 'color' => 'red', 'sort_order' => 4],
            ['code' => 'paid', 'name' => 'Paid', 'description' => 'Time card has been paid', 'color' => 'purple', 'sort_order' => 5],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_card_statuses');
    }
};