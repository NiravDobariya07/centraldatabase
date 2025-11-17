<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('campaign_list_ids', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary Key
            $table->string('list_id')->unique(); // The original campaign list ID
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('campaign_list_ids');
    }
};
