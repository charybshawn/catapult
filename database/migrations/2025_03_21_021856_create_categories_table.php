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
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        
        // Add category_id to items table if it doesn't exist
        if (!Schema::hasColumn('items', 'category_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key and column if they exist
        if (Schema::hasColumn('items', 'category_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }
        
        Schema::dropIfExists('categories');
    }
};
