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
        Schema::create('blacklist_listings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 100)->nullable();
            $table->string('response', 255)->nullable();
            $table->string('source_type', 45)->nullable();
            $table->string('source', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        // Set table engine to InnoDB and charset to utf8mb4
        DB::statement('ALTER TABLE `blacklist_listings` ENGINE = InnoDB');
        DB::statement('ALTER TABLE `blacklist_listings` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklist_listings');
    }
};
