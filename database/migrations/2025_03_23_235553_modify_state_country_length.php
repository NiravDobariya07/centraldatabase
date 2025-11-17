<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('state', 50)->nullable()->change();
            $table->string('country', 60)->nullable()->change();
        });
    }

    public function down() {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('state', 5)->nullable()->change();
            $table->string('country', 5)->nullable()->change();
        });
    }
};