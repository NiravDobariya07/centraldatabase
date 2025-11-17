<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('source_site_id')->nullable()->after('page_url');
            $table->foreign('source_site_id')->references('id')->on('source_sites')->onDelete('set null');
        });
    }

    public function down() {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['source_site_id']);
            $table->dropColumn('source_site_id');
        });
    }
};
