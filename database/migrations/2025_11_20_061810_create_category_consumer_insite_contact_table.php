<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure both parent tables exist before creating pivot table
        if (!Schema::hasTable('consumer_insite_contacts')) {
            throw new \Exception('consumer_insite_contacts table must exist before creating pivot table');
        }

        if (!Schema::hasTable('categories')) {
            throw new \Exception('categories table must exist before creating pivot table');
        }

        Schema::create('category_consumer_insite_contact', function (Blueprint $table) {
            $table->unsignedInteger('consumer_insite_contact_id');
            $table->unsignedInteger('category_id');
            $table->timestamps();

            $table->primary(['consumer_insite_contact_id', 'category_id']);
            $table->foreign('consumer_insite_contact_id', 'cic_contact_id_foreign')
                ->references('id')
                ->on('consumer_insite_contacts')
                ->onDelete('cascade');
            $table->foreign('category_id', 'cic_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_consumer_insite_contact', function (Blueprint $table) {
            $table->dropForeign('cic_contact_id_foreign');
            $table->dropForeign('cic_category_id_foreign');
        });
        Schema::dropIfExists('category_consumer_insite_contact');
    }
};
