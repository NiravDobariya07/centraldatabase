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
        Schema::create('tra_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->timestamp('lead_time_stamp')->useCurrent()->useCurrentOnUpdate();
            $table->string('email', 100);
            $table->string('email_domain', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('state', 50);
            $table->string('zip_code', 10);
            $table->string('page', 255)->nullable();
            $table->string('optin_domain', 100)->nullable();
            $table->string('universal_leadid', 500)->nullable();
            $table->string('cake_id', 255);
            $table->string('ckm_campaign_id', 255);
            $table->string('ckm_key', 255);
            $table->string('tax_debt', 12);
            $table->string('aff_id', 50)->nullable();
            $table->string('sub_id', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('offer_id', 50)->nullable();
            $table->text('response')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        // Set table engine to InnoDB and charset to utf8mb3
        DB::statement('ALTER TABLE `tra_contacts` ENGINE = InnoDB');
        DB::statement('ALTER TABLE `tra_contacts` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');

        // Set character set for each column
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `first_name` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `last_name` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `email` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `email_domain` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `phone` VARCHAR(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `state` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `zip_code` VARCHAR(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `page` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `optin_domain` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `universal_leadid` VARCHAR(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `cake_id` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `ckm_campaign_id` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `ckm_key` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `tax_debt` VARCHAR(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `aff_id` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `sub_id` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `ip_address` VARCHAR(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `offer_id` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `tra_contacts` MODIFY `response` TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tra_contacts');
    }
};
