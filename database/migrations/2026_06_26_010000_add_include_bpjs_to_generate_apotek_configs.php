<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_apotek_configs')) {
            return;
        }

        if (! Schema::hasColumn('generate_apotek_configs', 'include_bpjs_in_umum')) {
            Schema::table('generate_apotek_configs', function (Blueprint $table) {
                $table->boolean('include_bpjs_in_umum')
                    ->default(false)
                    ->after('source_period_mode');
            });
        }

        DB::table('generate_apotek_configs')
            ->where('jenis_apotek', 'bpjs')
            ->update(['include_bpjs_in_umum' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_apotek_configs')) {
            return;
        }

        if (Schema::hasColumn('generate_apotek_configs', 'include_bpjs_in_umum')) {
            Schema::table('generate_apotek_configs', function (Blueprint $table) {
                $table->dropColumn('include_bpjs_in_umum');
            });
        }
    }
};
