<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('leads', function (Blueprint $table) {
            // Add search_vector column if you still want it
            if (!Schema::hasColumn('leads', 'search_vector')) {
                $table->text('search_vector')->nullable();
            }

            // ✅ Create FULLTEXT index instead of GIN
            $table->fullText(
                ['first_name', 'last_name', 'email', 'phone', 'city', 'state', 'type_of_debt'],
                'leads_search_vector_idx'
            );
        });
    }

    public function down() {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'search_vector')) {
                $table->dropColumn('search_vector');
            }

            // ✅ Drop FULLTEXT index properly
            $table->dropIndex('leads_search_vector_idx');
        });
    }
};
