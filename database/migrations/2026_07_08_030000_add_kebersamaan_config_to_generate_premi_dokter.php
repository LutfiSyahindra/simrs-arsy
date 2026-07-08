<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                if (! Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_umum_percent')) {
                    $table->decimal('kebersamaan_umum_percent', 8, 4)->default(30)->after('source_period_mode');
                }

                if (! Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_bpjs_nominal')) {
                    $table->unsignedBigInteger('kebersamaan_bpjs_nominal')->default(40000)->after('kebersamaan_umum_percent');
                }

                if (! Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_bpjs_percent')) {
                    $table->decimal('kebersamaan_bpjs_percent', 8, 4)->default(30)->after('kebersamaan_bpjs_nominal');
                }

                if (! Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_divider')) {
                    $table->unsignedInteger('kebersamaan_divider')->default(4)->after('kebersamaan_bpjs_percent');
                }
            });
        }

        if (Schema::hasTable('generate_premi_dokter_config_doctor')) {
            if ($this->indexExists('generate_premi_dokter_config_doctor', 'gpd_cfg_doctor_unique')) {
                Schema::table('generate_premi_dokter_config_doctor', function (Blueprint $table) {
                    $table->dropUnique('gpd_cfg_doctor_unique');
                });
            }

            if (! $this->indexExists('generate_premi_dokter_config_doctor', 'gpd_cfg_doctor_category_unique')) {
                Schema::table('generate_premi_dokter_config_doctor', function (Blueprint $table) {
                    $table->unique(['config_id', 'kategori', 'kd_dokter'], 'gpd_cfg_doctor_category_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('generate_premi_dokter_config_doctor')) {
            if ($this->indexExists('generate_premi_dokter_config_doctor', 'gpd_cfg_doctor_category_unique')) {
                Schema::table('generate_premi_dokter_config_doctor', function (Blueprint $table) {
                    $table->dropUnique('gpd_cfg_doctor_category_unique');
                });
            }

            if (! $this->indexExists('generate_premi_dokter_config_doctor', 'gpd_cfg_doctor_unique')) {
                DB::statement("
                    DELETE duplicate_doctor
                    FROM generate_premi_dokter_config_doctor AS duplicate_doctor
                    INNER JOIN generate_premi_dokter_config_doctor AS keep_doctor
                        ON keep_doctor.config_id = duplicate_doctor.config_id
                        AND keep_doctor.kd_dokter = duplicate_doctor.kd_dokter
                        AND keep_doctor.id < duplicate_doctor.id
                ");

                Schema::table('generate_premi_dokter_config_doctor', function (Blueprint $table) {
                    $table->unique(['config_id', 'kd_dokter'], 'gpd_cfg_doctor_unique');
                });
            }
        }

        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                foreach ([
                    'kebersamaan_umum_percent',
                    'kebersamaan_bpjs_nominal',
                    'kebersamaan_bpjs_percent',
                    'kebersamaan_divider',
                ] as $column) {
                    if (Schema::hasColumn('generate_premi_dokter_configs', $column)) {
                        $table->dropColumn($column);
                    }
                }
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
