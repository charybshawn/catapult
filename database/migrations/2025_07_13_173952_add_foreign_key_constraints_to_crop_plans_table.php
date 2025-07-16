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
            // Add foreign key constraint for order_id to automatically delete crop plans when orders are deleted
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crop_plans', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
    }
};
