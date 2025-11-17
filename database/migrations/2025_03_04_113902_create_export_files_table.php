<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('export_files', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Foreign key linking to exports table
            $table->foreignId('export_id')
                ->constrained('exports') // Ensure reference integrity
                ->onDelete('cascade'); // If export is deleted, delete its files too

            // Foreign key linking to users table
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Ensure file is tied to user

            $table->string('file_name'); // Actual file name
            $table->string('file_path'); // Storage path
            $table->string('file_format'); // csv, xlsx, pdf, etc.
            $table->bigInteger('file_size')->nullable(); // Size in KB

            $table->timestamp('generated_at')->useCurrent(); // Timestamp when file was generated
            $table->timestamp('expires_at')->nullable(); // Optional expiry date for cleanup

            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('export_id');
            $table->index('file_format');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('export_files');
    }
};
