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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category_name', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Set table engine to InnoDB (required for foreign keys) and charset to utf8mb3
        DB::statement('ALTER TABLE `categories` ENGINE = InnoDB');
        DB::statement('ALTER TABLE `categories` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');

        // Set character set and collation for category_name column
        DB::statement('ALTER TABLE `categories` MODIFY `category_name` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
