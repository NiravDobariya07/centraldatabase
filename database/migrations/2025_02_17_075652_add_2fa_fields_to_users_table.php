<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_fa_enabled')->default(true)->after('password'); // Default enabled
            $table->enum('two_fa_method', ['email', 'authenticator_app'])->default('email')->after('two_fa_enabled'); // Default email
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_fa_enabled', 'two_fa_method']);
        });
    }
};
