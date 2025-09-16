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
        Schema::create('users', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('name', 255);
                    $table->string('email', 255);
                    $table->string('phone', 255)->nullable();
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password', 255)->nullable();
                    $table->bigInteger('customer_type_id')->nullable();
                    $table->decimal('wholesale_discount_percentage', 5, 2)->nullable();
                    $table->string('company_name', 255)->nullable();
                    $table->text('address')->nullable();
                    $table->string('city', 255)->nullable();
                    $table->string('state', 255)->nullable();
                    $table->string('zip', 255)->nullable();
                    $table->string('remember_token', 100)->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
