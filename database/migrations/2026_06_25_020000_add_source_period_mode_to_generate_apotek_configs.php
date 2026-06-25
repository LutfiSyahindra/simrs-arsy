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

        if (! Schema::hasColumn('generate_apotek_configs', 'source_period_mode')) {
            Schema::table('generate_apotek_configs', function (Blueprint $table) {
                $table->string('source_period_mode', 20)
                    ->default('previous')
                    ->after('tarif_per_item');
            });
        }

        DB::table('generate_apotek_configs')
            ->where('jenis_apotek', 'umum')
            ->update(['source_period_mode' => 'current']);

        DB::table('generate_apotek_configs')
            ->where('jenis_apotek', 'bpjs')
            ->where(function ($query) {
                $query->whereNull('source_period_mode')
                    ->orWhereNotIn('source_period_mode', ['previous', 'current']);
            })
            ->update(['source_period_mode' => 'previous']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_apotek_configs')) {
            return;
        }

        if (Schema::hasColumn('generate_apotek_configs', 'source_period_mode')) {
            Schema::table('generate_apotek_configs', function (Blueprint $table) {
                $table->dropColumn('source_period_mode');
            });
        }
    }
};
