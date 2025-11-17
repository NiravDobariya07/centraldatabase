<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('title')->index();
            $table->text('description')->nullable();

            // ✅ Changed from text → string (can index safely)
            $table->string('file_prefix', 255)->nullable();

            $table->json('export_formats');
            $table->json('filters')->nullable();
            $table->json('additional_data')->nullable();
            $table->json('columns')->nullable();

            $table->enum('frequency', ['one_time', 'daily', 'weekly', 'monthly', 'custom'])->index();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->integer('day_of_month')->nullable();
            $table->time('time')->nullable();

            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->enum('runing_status', ['scheduled', 'success', 'failed', 'pending'])->nullable();

            $table->enum('status', ['active', 'paused', 'stopped'])->default('active')->index();

            $table->timestamps();

            // ✅ Index safely on string
            $table->index('file_prefix');
            $table->index(['frequency', 'day_of_week', 'day_of_month']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('exports');
    }
};
