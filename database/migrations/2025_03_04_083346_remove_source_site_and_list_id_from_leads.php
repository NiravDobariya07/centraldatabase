<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['source_site', 'list_id']);
        });
    }

    public function down() {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source_site')->nullable();
            $table->string('list_id')->nullable();
        });
    }
};
