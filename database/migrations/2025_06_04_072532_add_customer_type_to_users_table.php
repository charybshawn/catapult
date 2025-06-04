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
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('retail')->after('phone');
            $table->string('company_name')->nullable()->after('customer_type');
            $table->text('address')->nullable()->after('company_name');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 50)->nullable()->after('city');
            $table->string('zip', 20)->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'company_name', 'address', 'city', 'state', 'zip']);
        });
    }
};
