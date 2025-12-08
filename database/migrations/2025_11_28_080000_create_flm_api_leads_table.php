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
        if (Schema::hasTable('flm_api_leads')) {
            return;
        }

        Schema::create('flm_api_leads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 255)->nullable();
            $table->string('email_address', 255)->nullable();
            $table->string('lead_timestamp', 255)->nullable();
            $table->longText('fetch_paid_response')->nullable();
            $table->string('payout_paid', 255)->nullable();
            $table->tinyInteger('eoapi_success')->default(0);
            $table->tinyInteger('is_email_duplicate')->default(0);
            $table->string('result', 30)->nullable();
            $table->integer('resultid')->nullable();
            $table->longText('response')->nullable();
            $table->tinyInteger('is_ongage')->default(0);
            $table->longText('ongage_response')->nullable();
            $table->timestamp('ongage_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('lead_id', 255)->nullable();
        });

        // Set table engine to InnoDB and charset to utf8mb4
        DB::statement('ALTER TABLE `flm_api_leads` ENGINE = InnoDB');
        DB::statement('ALTER TABLE `flm_api_leads` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');

        // Set character set for specific columns that use utf8mb3
        DB::statement('ALTER TABLE `flm_api_leads` MODIFY `result` VARCHAR(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `flm_api_leads` MODIFY `response` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        DB::statement('ALTER TABLE `flm_api_leads` MODIFY `ongage_response` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flm_api_leads');
    }
};

