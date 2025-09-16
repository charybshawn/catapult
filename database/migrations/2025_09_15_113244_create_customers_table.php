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
        Schema::create('customers', function (Blueprint $table) {
                    $table->id('id');
                    $table->string('contact_name', 255);
                    $table->string('email', 255);
                    $table->string('cc_email', 255)->nullable();
                    $table->string('phone', 255)->nullable();
                    $table->bigInteger('customer_type_id');
                    $table->string('business_name', 255)->nullable();
                    $table->decimal('wholesale_discount_percentage', 5, 2);
                    $table->text('address')->nullable();
                    $table->string('city', 255)->nullable();
                    $table->string('province', 255)->nullable();
                    $table->string('postal_code', 20)->nullable();
                    $table->string('country', 2);
                    $table->bigInteger('user_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
