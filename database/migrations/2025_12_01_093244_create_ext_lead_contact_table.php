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
        Schema::create('ext_lead_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 191);
            $table->string('phone', 20)->nullable();
            $table->string('alt_phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('postal', 20)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('ip', 50)->nullable();
            $table->string('date_subscribed', 50)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('offer_url', 255)->nullable();
            $table->string('dob', 255)->nullable();
            $table->string('list_id', 255)->nullable();
            $table->string('import_date', 255)->nullable();
            $table->string('phone_type', 50)->nullable();
            $table->string('tax_debt_amount', 100)->nullable();
            $table->string('type_of_debt', 100)->nullable();
            $table->string('homeowner', 10)->nullable();
            $table->string('jornaya_id', 255)->nullable();
            $table->string('trusted_form_id', 255)->nullable();
            $table->string('opt_in', 10)->nullable();
            $table->string('subid1', 100)->nullable();
            $table->string('subid2', 100)->nullable();
            $table->string('subid3', 100)->nullable();
            $table->string('subid4', 100)->nullable();
            $table->string('subid5', 100)->nullable();
            $table->string('aff_id_1', 100)->nullable();
            $table->string('aff_id_2', 100)->nullable();
            $table->string('lead_id', 100)->nullable();
            $table->string('page_url', 255)->nullable();
            $table->string('ef_id', 100)->nullable();
            $table->string('ck_id', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->string('affid', 100)->nullable();
            $table->string('subid', 100)->nullable();
            $table->string('result', 30)->nullable();
            $table->integer('resultid')->nullable();
            $table->longText('response')->nullable();
            $table->tinyInteger('is_email_duplicate')->default(0);
            $table->tinyInteger('eoapi_success')->default(0);
            $table->boolean('is_ongage')->default(0);
            $table->longText('ongage_response')->nullable();
            $table->timestamp('ongage_at')->nullable();
            $table->timestamp('created_date')->nullable()->useCurrent();
            $table->timestamp('updated_date')->nullable()->useCurrent();
            $table->timestamp('deleted_date')->nullable();
        });

        // Set table engine to InnoDB and charset to utf8mb4
        DB::statement('ALTER TABLE `ext_lead_contact` ENGINE = InnoDB');
        DB::statement('ALTER TABLE `ext_lead_contact` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');

        // Set character set for specific columns that use utf8mb3
        DB::statement('ALTER TABLE `ext_lead_contact` MODIFY `result` VARCHAR(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `ext_lead_contact` MODIFY `response` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        DB::statement('ALTER TABLE `ext_lead_contact` MODIFY `ongage_response` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_lead_contact');
    }
};
