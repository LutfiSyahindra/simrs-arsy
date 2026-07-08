<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_premi_dokter_config_tindakan')) {
            return;
        }

        if (! Schema::hasColumn('generate_premi_dokter_config_tindakan', 'jnsTindakan_id')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->unsignedBigInteger('jnsTindakan_id')->nullable()->after('config_id');
            });
        }

        if (! Schema::hasColumn('generate_premi_dokter_config_tindakan', 'mapping_tindakan_id')) {
            return;
        }

        DB::statement("
            UPDATE generate_premi_dokter_config_tindakan AS ct
            INNER JOIN mapping_tindakan AS mt ON mt.id = ct.mapping_tindakan_id
            SET ct.jnsTindakan_id = mt.jnsTindakan_id
        ");

        DB::table('generate_premi_dokter_config_tindakan')
            ->whereNull('jnsTindakan_id')
            ->delete();

        DB::statement("
            DELETE duplicate_ct
            FROM generate_premi_dokter_config_tindakan AS duplicate_ct
            INNER JOIN generate_premi_dokter_config_tindakan AS keep_ct
                ON keep_ct.config_id = duplicate_ct.config_id
                AND keep_ct.jnsTindakan_id = duplicate_ct.jnsTindakan_id
                AND keep_ct.id < duplicate_ct.id
        ");

        if ($this->foreignKeyExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_mapping_fk')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->dropForeign('gpd_cfg_tindakan_mapping_fk');
            });
        }

        if (! $this->indexExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_config_index')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->index('config_id', 'gpd_cfg_tindakan_config_index');
            });
        }

        if ($this->indexExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_unique')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->dropUnique('gpd_cfg_tindakan_unique');
            });
        }

        Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
            $table->dropColumn('mapping_tindakan_id');
        });

        Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
            if (! $this->foreignKeyExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_jns_fk')) {
                $table->foreign('jnsTindakan_id', 'gpd_cfg_tindakan_jns_fk')
                    ->references('id')
                    ->on('master_jenis_tindakan')
                    ->cascadeOnDelete();
            }

            if (! $this->indexExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_unique')) {
                $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_cfg_tindakan_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_premi_dokter_config_tindakan')) {
            return;
        }

        if (! Schema::hasColumn('generate_premi_dokter_config_tindakan', 'mapping_tindakan_id')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->unsignedBigInteger('mapping_tindakan_id')->nullable()->after('config_id');
            });
        }

        if (! Schema::hasColumn('generate_premi_dokter_config_tindakan', 'jnsTindakan_id')) {
            return;
        }

        DB::statement("
            UPDATE generate_premi_dokter_config_tindakan AS ct
            INNER JOIN (
                SELECT MIN(id) AS id, jnsTindakan_id
                FROM mapping_tindakan
                GROUP BY jnsTindakan_id
            ) AS mt ON mt.jnsTindakan_id = ct.jnsTindakan_id
            SET ct.mapping_tindakan_id = mt.id
        ");

        DB::table('generate_premi_dokter_config_tindakan')
            ->whereNull('mapping_tindakan_id')
            ->delete();

        if ($this->foreignKeyExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_jns_fk')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->dropForeign('gpd_cfg_tindakan_jns_fk');
            });
        }

        if (! $this->indexExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_config_index')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->index('config_id', 'gpd_cfg_tindakan_config_index');
            });
        }

        if ($this->indexExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_unique')) {
            Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
                $table->dropUnique('gpd_cfg_tindakan_unique');
            });
        }

        Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
            $table->dropColumn('jnsTindakan_id');
        });

        Schema::table('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
            if (! $this->foreignKeyExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_mapping_fk')) {
                $table->foreign('mapping_tindakan_id', 'gpd_cfg_tindakan_mapping_fk')
                    ->references('id')
                    ->on('mapping_tindakan')
                    ->cascadeOnDelete();
            }

            if (! $this->indexExists('generate_premi_dokter_config_tindakan', 'gpd_cfg_tindakan_unique')) {
                $table->unique(['config_id', 'mapping_tindakan_id'], 'gpd_cfg_tindakan_unique');
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
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
