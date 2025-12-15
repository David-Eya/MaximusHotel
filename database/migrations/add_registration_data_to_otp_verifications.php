<?php

/**
 * Migration to add registration_data column to otp_verifications table
 * 
 * Run this SQL on your database:
 * 
 * ALTER TABLE `otp_verifications` 
 * ADD COLUMN `registration_data` TEXT NULL AFTER `is_verified`;
 * 
 * Or use Laravel migration if you have migrations set up:
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrationDataToOtpVerifications extends Migration
{
    public function up()
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->text('registration_data')->nullable()->after('is_verified');
        });
    }

    public function down()
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn('registration_data');
        });
    }
}


