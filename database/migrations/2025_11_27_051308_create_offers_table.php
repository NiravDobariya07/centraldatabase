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
        Schema::create('offers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('offer_name', 255)->nullable(false);
            $table->string('domain_abt', 255)->nullable();
            $table->string('auth_token', 100)->nullable(false);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();
        });

        // Set table engine to MyISAM and charset to utf8mb3
        DB::statement('ALTER TABLE `offers` ENGINE = MyISAM');
        DB::statement('ALTER TABLE `offers` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');

        // Set character set for each column
        DB::statement('ALTER TABLE `offers` MODIFY `offer_name` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `offers` MODIFY `domain_abt` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        DB::statement('ALTER TABLE `offers` MODIFY `auth_token` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        DB::statement('ALTER TABLE `offers` MODIFY `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE `offers` MODIFY `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE `offers` MODIFY `deleted_at` TIMESTAMP NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
