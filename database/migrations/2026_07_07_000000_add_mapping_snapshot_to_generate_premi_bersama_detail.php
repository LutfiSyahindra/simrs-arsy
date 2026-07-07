<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('generate_premi_bersama_detail')
            && ! Schema::hasColumn('generate_premi_bersama_detail', 'mapping_snapshot')
        ) {
            Schema::table('generate_premi_bersama_detail', function (Blueprint $table) {
                $table->json('mapping_snapshot')->nullable()->after('source_rules');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('generate_premi_bersama_detail')
            && Schema::hasColumn('generate_premi_bersama_detail', 'mapping_snapshot')
        ) {
            Schema::table('generate_premi_bersama_detail', function (Blueprint $table) {
                $table->dropColumn('mapping_snapshot');
            });
        }
    }
};
