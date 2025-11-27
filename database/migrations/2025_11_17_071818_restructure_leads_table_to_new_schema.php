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
        // Drop foreign keys first if they exist
        try {
            DB::statement('ALTER TABLE leads DROP FOREIGN KEY leads_source_site_id_foreign');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }

        try {
            DB::statement('ALTER TABLE leads DROP FOREIGN KEY leads_campaign_list_id_foreign');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }

        // Drop old indexes if they exist
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

        // Rename columns using raw SQL (more reliable across MySQL versions)
        DB::statement('ALTER TABLE leads CHANGE COLUMN ip ip_address VARCHAR(45) NULL');
        DB::statement('ALTER TABLE leads CHANGE COLUMN jornaya_id journya VARCHAR(255) NULL');
        DB::statement('ALTER TABLE leads CHANGE COLUMN trusted_form_id trusted_form VARCHAR(255) NULL');
        DB::statement('ALTER TABLE leads CHANGE COLUMN lead_id cake_leadid VARCHAR(255) NULL');

        // Drop old columns that are not in new schema
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'alt_phone')) {
                $table->dropColumn('alt_phone');
            }
            if (Schema::hasColumn('leads', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('leads', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('leads', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('leads', 'postal')) {
                $table->dropColumn('postal');
            }
            if (Schema::hasColumn('leads', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('leads', 'date_subscribed')) {
                $table->dropColumn('date_subscribed');
            }
            if (Schema::hasColumn('leads', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('leads', 'offer_url')) {
                $table->dropColumn('offer_url');
            }
            if (Schema::hasColumn('leads', 'dob')) {
                $table->dropColumn('dob');
            }
            if (Schema::hasColumn('leads', 'tax_debt_amount')) {
                $table->dropColumn('tax_debt_amount');
            }
            if (Schema::hasColumn('leads', 'cc_debt_amount')) {
                $table->dropColumn('cc_debt_amount');
            }
            if (Schema::hasColumn('leads', 'type_of_debt')) {
                $table->dropColumn('type_of_debt');
            }
            if (Schema::hasColumn('leads', 'home_owner')) {
                $table->dropColumn('home_owner');
            }
            if (Schema::hasColumn('leads', 'import_date')) {
                $table->dropColumn('import_date');
            }
            if (Schema::hasColumn('leads', 'phone_type')) {
                $table->dropColumn('phone_type');
            }
            if (Schema::hasColumn('leads', 'opt_in')) {
                $table->dropColumn('opt_in');
            }
            if (Schema::hasColumn('leads', 'sub_id_1')) {
                $table->dropColumn(['sub_id_1', 'sub_id_2', 'sub_id_3', 'sub_id_4', 'sub_id_5']);
            }
            if (Schema::hasColumn('leads', 'aff_id_1')) {
                $table->dropColumn(['aff_id_1', 'aff_id_2']);
            }
            if (Schema::hasColumn('leads', 'ef_id')) {
                $table->dropColumn('ef_id');
            }
            if (Schema::hasColumn('leads', 'ck_id')) {
                $table->dropColumn('ck_id');
            }
            if (Schema::hasColumn('leads', 'page_url')) {
                $table->dropColumn('page_url');
            }
            if (Schema::hasColumn('leads', 'extra_fields')) {
                $table->dropColumn('extra_fields');
            }
            if (Schema::hasColumn('leads', 'search_vector')) {
                $table->dropColumn('search_vector');
            }
            if (Schema::hasColumn('leads', 'source_site_id')) {
                $table->dropColumn('source_site_id');
            }
            if (Schema::hasColumn('leads', 'campaign_list_id')) {
                $table->dropColumn('campaign_list_id');
            }
        });

        // Add new columns
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'email_domain')) {
                $table->string('email_domain')->nullable()->after('email');
            }
            if (!Schema::hasColumn('leads', 'optin_domain')) {
                $table->string('optin_domain')->nullable()->after('email_domain');
            }
            if (!Schema::hasColumn('leads', 'domain_abt')) {
                $table->string('domain_abt')->nullable()->after('optin_domain');
            }
            if (!Schema::hasColumn('leads', 'aff_id')) {
                $table->string('aff_id')->nullable()->after('domain_abt');
            }
            if (!Schema::hasColumn('leads', 'sub_id')) {
                $table->string('sub_id')->nullable()->after('aff_id');
            }
            if (!Schema::hasColumn('leads', 'result')) {
                $table->string('result')->nullable()->after('sub_id');
            }
            if (!Schema::hasColumn('leads', 'resultid')) {
                $table->string('resultid')->nullable()->after('result');
            }
            if (!Schema::hasColumn('leads', 'response')) {
                $table->text('response')->nullable()->after('resultid');
            }
            if (!Schema::hasColumn('leads', 'esp')) {
                $table->string('esp')->nullable()->after('response');
            }
            if (!Schema::hasColumn('leads', 'offer_id')) {
                $table->string('offer_id')->nullable()->after('esp');
            }
            if (!Schema::hasColumn('leads', 'is_email_duplicate')) {
                $table->boolean('is_email_duplicate')->default(false)->after('offer_id');
            }
            if (!Schema::hasColumn('leads', 'eoapi_success')) {
                $table->boolean('eoapi_success')->default(false)->after('is_email_duplicate');
            }
            if (!Schema::hasColumn('leads', 'is_ongage')) {
                $table->boolean('is_ongage')->default(false)->after('eoapi_success');
            }
            if (!Schema::hasColumn('leads', 'ongage_response')) {
                $table->text('ongage_response')->nullable()->after('is_ongage');
            }
            if (!Schema::hasColumn('leads', 'ongage_at')) {
                $table->timestamp('ongage_at')->nullable()->after('ongage_response');
            }
        });

        // Add indexes for new columns
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'email_domain') && !$this->indexExists('leads', 'leads_email_domain_index')) {
                $table->index('email_domain');
            }
            if (Schema::hasColumn('leads', 'optin_domain') && !$this->indexExists('leads', 'leads_optin_domain_index')) {
                $table->index('optin_domain');
            }
            if (Schema::hasColumn('leads', 'cake_leadid') && !$this->indexExists('leads', 'leads_cake_leadid_index')) {
                $table->index('cake_leadid');
            }
            if (Schema::hasColumn('leads', 'journya') && !$this->indexExists('leads', 'leads_journya_index')) {
                $table->index('journya');
            }
            if (Schema::hasColumn('leads', 'trusted_form') && !$this->indexExists('leads', 'leads_trusted_form_index')) {
                $table->index('trusted_form');
            }
            if (Schema::hasColumn('leads', 'ip_address') && !$this->indexExists('leads', 'leads_ip_address_index')) {
                $table->index('ip_address');
            }
        });
    }

    /**
     * Check if an index exists
     */
    private function indexExists($table, $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a complex migration, rollback would require recreating all dropped columns
        // For safety, we'll leave this as a placeholder
        // In production, you should backup data before running this migration
    }
};
