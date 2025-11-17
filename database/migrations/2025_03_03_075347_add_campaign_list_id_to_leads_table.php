<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('leads', function (Blueprint $table) {
            // Add campaign_list_id as a nullable foreign key
            $table->unsignedBigInteger('campaign_list_id')->nullable()->after('id');

            // Add foreign key constraint
            $table->foreign('campaign_list_id')->references('id')->on('campaign_list_ids')->onDelete('set null');
        });
    }

    public function down() {
        Schema::table('leads', function (Blueprint $table) {
            // Drop foreign key constraint before removing column
            $table->dropForeign(['campaign_list_id']);

            // Remove the column
            $table->dropColumn('campaign_list_id');
        });
    }
};
