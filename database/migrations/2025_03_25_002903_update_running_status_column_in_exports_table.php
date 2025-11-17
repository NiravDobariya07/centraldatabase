<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Step 1: Rename old column
        Schema::table('exports', function (Blueprint $table) {
            $table->renameColumn('runing_status', 'old_runing_status');
        });

        // Step 2: Create new column with updated values and default 'pending'
        Schema::table('exports', function (Blueprint $table) {
            $table->enum('runing_status', [
                'scheduled', 'success', 'failed', 'pending', 'paused', 'stopped'
            ])->default('pending')->nullable();
        });

        // Step 3: Copy old values to new column (✅ removed "::text")
        DB::statement("UPDATE exports SET runing_status = COALESCE(old_runing_status, 'pending')");

        // Step 4: Drop the old column
        Schema::table('exports', function (Blueprint $table) {
            $table->dropColumn('old_runing_status');
        });
    }

    public function down()
    {
        // Reverse: Drop new column and restore old one
        Schema::table('exports', function (Blueprint $table) {
            $table->dropColumn('runing_status');
        });

        Schema::table('exports', function (Blueprint $table) {
            $table->enum('runing_status', [
                'scheduled', 'success', 'failed', 'pending'
            ])->default('pending')->nullable();
        });
    }
};
