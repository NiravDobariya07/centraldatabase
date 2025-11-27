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
        Schema::create('all_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->dateTime('lead_time_stamp')->nullable();
            $table->string('email', 255)->nullable();
            $table->string('email_domain', 100)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('optin_domain', 255)->nullable();
            $table->string('domain_abt', 255)->nullable();
            $table->string('aff_id', 100)->nullable();
            $table->string('sub_id', 100)->nullable();
            $table->string('cake_leadid', 45)->nullable();
            $table->string('result', 30)->nullable();
            $table->integer('resultid')->nullable();
            $table->longText('response')->nullable();
            $table->string('journya', 100)->nullable();
            $table->string('trusted_form', 255)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->string('esp', 100)->nullable();
            $table->integer('offer_id')->nullable();
            $table->tinyInteger('is_email_duplicate')->default(0);
            $table->integer('list_id')->nullable();
            $table->tinyInteger('eoapi_success')->default(0);
            $table->tinyInteger('is_ongage')->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->longText('ongage_response')->nullable();
            $table->timestamp('ongage_at')->nullable();

            // Composite index on email_domain and created_at
            $table->index(['email_domain', 'created_at'], 'email_domain');
        });

        // Set table engine to MyISAM and charset to utf8mb3
        DB::statement('ALTER TABLE `all_contacts` ENGINE = MyISAM');
        DB::statement('ALTER TABLE `all_contacts` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');

        // Set character set and collation for each column
        DB::statement('ALTER TABLE `all_contacts` MODIFY `first_name` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `last_name` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `email` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `email_domain` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `phone` VARCHAR(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `optin_domain` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `domain_abt` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `aff_id` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `sub_id` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `cake_leadid` VARCHAR(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `result` VARCHAR(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `response` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `journya` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `trusted_form` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `ip_address` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `esp` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `all_contacts` MODIFY `ongage_response` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_contacts');
    }
};
