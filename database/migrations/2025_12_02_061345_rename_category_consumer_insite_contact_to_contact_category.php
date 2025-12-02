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
        // Check if the old table exists before renaming
        if (Schema::hasTable('category_consumer_insite_contact')) {
            // Drop foreign keys first
            Schema::table('category_consumer_insite_contact', function (Blueprint $table) {
                $table->dropForeign('cic_contact_id_foreign');
                $table->dropForeign('cic_category_id_foreign');
            });

            // Rename the table
            DB::statement('RENAME TABLE `category_consumer_insite_contact` TO `contact_category`');

            // Recreate foreign keys with new names
            Schema::table('contact_category', function (Blueprint $table) {
                $table->foreign('consumer_insite_contact_id', 'cc_contact_id_foreign')
                    ->references('id')
                    ->on('consumer_insite_contacts')
                    ->onDelete('cascade');
                $table->foreign('category_id', 'cc_category_id_foreign')
                    ->references('id')
                    ->on('categories')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the new table exists before renaming back
        if (Schema::hasTable('contact_category')) {
            // Drop foreign keys first
            Schema::table('contact_category', function (Blueprint $table) {
                $table->dropForeign('cc_contact_id_foreign');
                $table->dropForeign('cc_category_id_foreign');
            });

            // Rename the table back
            DB::statement('RENAME TABLE `contact_category` TO `category_consumer_insite_contact`');

            // Recreate foreign keys with old names
            Schema::table('category_consumer_insite_contact', function (Blueprint $table) {
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
    }
};
