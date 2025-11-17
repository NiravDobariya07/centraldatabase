<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedToDispatchLeadsTable extends Migration
{
    public function up()
    {
        Schema::create('failed_to_dispatch_leads', function (Blueprint $table) {
            $table->id();
            $table->json('payload'); // To store the lead data as JSON
            $table->text('error_message')->nullable();
            $table->integer('exception_code')->nullable();
            $table->text('exception_file')->nullable();
            $table->integer('exception_line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->text('client_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('request_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('failed_to_dispatch_leads');
    }
}