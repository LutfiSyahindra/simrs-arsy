<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasColumn('generate_premi_dokter_configs', 'source_period_mode')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                $table->string('source_period_mode', 20)->default('current')->after('visite_bpjs_nominal');
            });
        }

        if (Schema::hasTable('generate_premi_dokter')
            && ! Schema::hasColumn('generate_premi_dokter', 'source_period_mode')) {
            Schema::table('generate_premi_dokter', function (Blueprint $table) {
                $table->string('source_period_mode', 20)->default('current')->after('source_periode');
            });
        }

        if (Schema::hasTable('generate_premi_dokter')
            && ! $this->indexExists('generate_premi_dokter', 'gpd_source_period_index')) {
            Schema::table('generate_premi_dokter', function (Blueprint $table) {
                $table->index('source_periode', 'gpd_source_period_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('generate_premi_dokter')
            && $this->indexExists('generate_premi_dokter', 'gpd_source_period_index')) {
            Schema::table('generate_premi_dokter', function (Blueprint $table) {
                $table->dropIndex('gpd_source_period_index');
            });
        }

        if (Schema::hasTable('generate_premi_dokter')
            && Schema::hasColumn('generate_premi_dokter', 'source_period_mode')) {
            Schema::table('generate_premi_dokter', function (Blueprint $table) {
                $table->dropColumn('source_period_mode');
            });
        }

        if (Schema::hasTable('generate_premi_dokter_configs')
            && Schema::hasColumn('generate_premi_dokter_configs', 'source_period_mode')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                $table->dropColumn('source_period_mode');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
