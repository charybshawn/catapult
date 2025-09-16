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
        Schema::create('suppliers', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('name', 255);
                    $table->bigInteger('supplier_type_id');
                    $table->string('contact_name', 255)->nullable();
                    $table->string('contact_email', 255)->nullable();
                    $table->string('contact_phone', 255)->nullable();
                    $table->text('address')->nullable();
                    $table->text('notes')->nullable();
                    $table->integer('is_active');
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
