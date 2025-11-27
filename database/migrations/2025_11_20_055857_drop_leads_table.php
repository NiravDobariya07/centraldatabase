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
        // Check if leads table exists before attempting to drop
        if (Schema::hasTable('leads')) {
            // Drop foreign keys first if they exist
            try {
                DB::statement('ALTER TABLE leads DROP FOREIGN KEY leads_source_site_id_foreign');
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }

            try {
                DB::statement('ALTER TABLE leads DROP FOREIGN KEY leads_campaign_list_id_foreign');
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }

            // Drop indexes if they exist
            try {
                Schema::table('leads', function (Blueprint $table) {
                    $table->dropIndex('leads_search_vector_idx');
                });
            } catch (\Exception $e) {
                // Index might not exist
            }

            try {
                Schema::table('leads', function (Blueprint $table) {
                    $table->dropIndex('leads_fulltext_idx');
                });
            } catch (\Exception $e) {
                // Index might not exist
            }

            // Drop the leads table
            Schema::dropIfExists('leads');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration cannot be fully reversed as the original leads table structure
        // has been modified by subsequent migrations. If you need to restore the leads table,
        // you would need to run the original create_leads_table migration and all subsequent
        // migrations that modified it.
    }
};
