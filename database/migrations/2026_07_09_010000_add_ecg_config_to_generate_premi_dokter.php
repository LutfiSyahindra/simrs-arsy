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
                if (! Schema::hasColumn('generate_premi_dokter_configs', 'ecg_nominal')) {
                    $table->unsignedBigInteger('ecg_nominal')->default(5000)->after('kebersamaan_divider');
                }

                if (! Schema::hasColumn('generate_premi_dokter_configs', 'ecg_divider')) {
                    $table->unsignedInteger('ecg_divider')->default(3)->after('ecg_nominal');
                }
            });
        }

        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasTable('generate_premi_dokter_ecg_config_tindakan')) {
            Schema::create('generate_premi_dokter_ecg_config_tindakan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('config_id');
                $table->unsignedBigInteger('jnsTindakan_id');
                $table->timestamps();

                $table->foreign('config_id', 'gpd_ecg_cfg_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
                $table->foreign('jnsTindakan_id', 'gpd_ecg_cfg_tindakan_jns_fk')
                    ->references('id')
                    ->on('master_jenis_tindakan')
                    ->cascadeOnDelete();
                $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_ecg_cfg_tindakan_unique');
                $table->index('config_id', 'gpd_ecg_cfg_tindakan_config_idx');
            });
        }

        if (Schema::hasTable('generate_premi_dokter_ecg_config_tindakan')) {
            if (! $this->foreignKeyExists('generate_premi_dokter_ecg_config_tindakan', 'gpd_ecg_cfg_config_fk')) {
                Schema::table('generate_premi_dokter_ecg_config_tindakan', function (Blueprint $table) {
                    $table->foreign('config_id', 'gpd_ecg_cfg_config_fk')
                        ->references('id')
                        ->on('generate_premi_dokter_configs')
                        ->cascadeOnDelete();
                });
            }

            if (! $this->foreignKeyExists('generate_premi_dokter_ecg_config_tindakan', 'gpd_ecg_cfg_tindakan_jns_fk')) {
                Schema::table('generate_premi_dokter_ecg_config_tindakan', function (Blueprint $table) {
                    $table->foreign('jnsTindakan_id', 'gpd_ecg_cfg_tindakan_jns_fk')
                        ->references('id')
                        ->on('master_jenis_tindakan')
                        ->cascadeOnDelete();
                });
            }

            if (! $this->indexExists('generate_premi_dokter_ecg_config_tindakan', 'gpd_ecg_cfg_tindakan_unique')) {
                Schema::table('generate_premi_dokter_ecg_config_tindakan', function (Blueprint $table) {
                    $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_ecg_cfg_tindakan_unique');
                });
            }

            if (! $this->indexExists('generate_premi_dokter_ecg_config_tindakan', 'gpd_ecg_cfg_tindakan_config_idx')) {
                Schema::table('generate_premi_dokter_ecg_config_tindakan', function (Blueprint $table) {
                    $table->index('config_id', 'gpd_ecg_cfg_tindakan_config_idx');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_dokter_ecg_config_tindakan');

        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_divider')) {
                    $table->dropColumn('ecg_divider');
                }

                if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_nominal')) {
                    $table->dropColumn('ecg_nominal');
                }
            });
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
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
