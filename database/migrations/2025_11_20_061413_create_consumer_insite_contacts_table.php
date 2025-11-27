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
        Schema::create('consumer_insite_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 100);
            $table->string('age', 60)->nullable();
            $table->string('credit_score', 60)->nullable();
            $table->string('location_name', 50);
            $table->tinyInteger('is_email_duplicate')->default(0);
            $table->tinyInteger('eoapi_success')->default(0);
            $table->string('result', 30)->nullable();
            $table->integer('resultid')->nullable();
            $table->longText('response')->nullable();
            $table->tinyInteger('is_ongage')->default(0);
            $table->longText('ongage_response')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->tinyInteger('deleted_at')->default(0);
        });

        // Set table engine to InnoDB and charset to latin1
        DB::statement('ALTER TABLE `consumer_insite_contacts` ENGINE = InnoDB');
        DB::statement('ALTER TABLE `consumer_insite_contacts` DEFAULT CHARACTER SET latin1');

        // Set character set for each column
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `first_name` VARCHAR(100) CHARACTER SET latin1 DEFAULT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `last_name` VARCHAR(100) CHARACTER SET latin1 DEFAULT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `email` VARCHAR(100) CHARACTER SET latin1 NOT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `age` VARCHAR(60) CHARACTER SET latin1 DEFAULT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `credit_score` VARCHAR(60) CHARACTER SET latin1 DEFAULT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `location_name` VARCHAR(50) CHARACTER SET latin1 NOT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `result` VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `response` LONGTEXT CHARACTER SET latin1');
        DB::statement('ALTER TABLE `consumer_insite_contacts` MODIFY `ongage_response` LONGTEXT CHARACTER SET latin1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_insite_contacts');
    }
};
