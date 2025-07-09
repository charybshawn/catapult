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
        Schema::table('customers', function (Blueprint $table) {
            // Rename name to contact_name
            $table->renameColumn('name', 'contact_name');
            
            // Rename company_name to business_name
            $table->renameColumn('company_name', 'business_name');
            
            // Change state to province
            $table->renameColumn('state', 'province');
            
            // Rename zip to postal_code
            $table->renameColumn('zip', 'postal_code');
            
            // Add cc_email after email
            $table->string('cc_email')->nullable()->after('email');
            
            // Drop the unique constraint on email to allow duplicates
            $table->dropUnique(['email']);
            
            // Make business_name available for all customer types
            $table->string('business_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('contact_name', 'name');
            $table->renameColumn('business_name', 'company_name');
            $table->renameColumn('province', 'state');
            $table->renameColumn('postal_code', 'zip');
            
            // Drop cc_email
            $table->dropColumn('cc_email');
            
            // Re-add unique constraint on email
            $table->unique('email');
        });
    }
};