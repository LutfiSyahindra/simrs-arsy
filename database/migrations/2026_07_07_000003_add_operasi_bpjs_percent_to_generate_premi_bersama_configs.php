<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('generate_premi_bersama_configs')
            && ! Schema::hasColumn('generate_premi_bersama_configs', 'operasi_bpjs_premi_bersama_percent')
        ) {
            Schema::table('generate_premi_bersama_configs', function (Blueprint $table) {
                $table->decimal('operasi_bpjs_premi_bersama_percent', 8, 4)
                    ->default(20)
                    ->after('bpjs_source_mode');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('generate_premi_bersama_configs')
            && Schema::hasColumn('generate_premi_bersama_configs', 'operasi_bpjs_premi_bersama_percent')
        ) {
            Schema::table('generate_premi_bersama_configs', function (Blueprint $table) {
                $table->dropColumn('operasi_bpjs_premi_bersama_percent');
            });
        }
    }
};
