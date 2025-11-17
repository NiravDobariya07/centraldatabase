<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alt_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 5)->nullable();
            $table->string('postal', 20)->nullable();
            $table->string('country', 5)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('date_subscribed')->nullable();
            $table->string('gender')->nullable();
            $table->text('offer_url')->nullable();
            $table->date('dob')->nullable();

            // Numeric fields stored as strings
            $table->decimal('tax_debt_amount', 15, 2)->nullable();
            $table->decimal('cc_debt_amount', 15, 2)->nullable();
            $table->string('type_of_debt')->nullable();
            $table->string('home_owner')->nullable();

            $table->string('list_id')->nullable();
            $table->timestamp('import_date')->nullable();
            $table->string('jornaya_id')->nullable();
            $table->string('phone_type')->nullable();
            $table->string('trusted_form_id')->nullable();
            $table->string('opt_in')->nullable();
            $table->string('sub_id_1')->nullable();
            $table->string('sub_id_2')->nullable();
            $table->string('sub_id_3')->nullable();
            $table->string('sub_id_4')->nullable();
            $table->string('sub_id_5')->nullable();
            $table->string('aff_id_1')->nullable();
            $table->string('aff_id_2')->nullable();

            // Identifier fields
            $table->string('lead_id')->nullable();
            $table->string('ef_id')->nullable();
            $table->string('ck_id')->nullable();

            $table->text('page_url')->nullable();
            $table->string('source_site')->nullable();  // extracted domain from page_url
            $table->mediumText('extra_fields')->nullable();

            $table->timestamps();

            // Composite indexes for common searches
            $table->index(['email', 'phone']);
            $table->index(['city', 'state', 'postal']);
            $table->index(['list_id', 'import_date']);
            $table->index(['tax_debt_amount', 'cc_debt_amount', 'type_of_debt']);

            // Indexes on additional identifier fields
            $table->index('list_id');
            $table->index('jornaya_id');
            $table->index('trusted_form_id');
            $table->index('lead_id');
            $table->index('ef_id');
            $table->index('ck_id');

            // ✅ Full-text search index (MySQL version)
            $table->fullText([
                'first_name',
                'last_name',
                'email',
                'phone',
                'city',
                'state',
                'type_of_debt',
            ], 'leads_fulltext_idx');
        });
    }

    public function down() {
        Schema::dropIfExists('leads');
    }
};
