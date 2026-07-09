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
            && ! Schema::hasTable('generate_premi_dokter_rawat_jalan_config_tindakan')) {
            Schema::create('generate_premi_dokter_rawat_jalan_config_tindakan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('config_id');
                $table->unsignedBigInteger('jnsTindakan_id');
                $table->string('multiplier_type', 20)->default('nominal');
                $table->decimal('multiplier_value', 20, 4)->default(0);
                $table->timestamps();

                $table->foreign('config_id', 'gpd_rj_cfg_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
                $table->foreign('jnsTindakan_id', 'gpd_rj_cfg_tindakan_jns_fk')
                    ->references('id')
                    ->on('master_jenis_tindakan')
                    ->cascadeOnDelete();
                $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_rj_cfg_tindakan_unique');
                $table->index('config_id', 'gpd_rj_cfg_tindakan_config_idx');
            });
        }

        if (Schema::hasTable('generate_premi_dokter_rawat_jalan_config_tindakan')) {
            if (! $this->foreignKeyExists('generate_premi_dokter_rawat_jalan_config_tindakan', 'gpd_rj_cfg_config_fk')) {
                Schema::table('generate_premi_dokter_rawat_jalan_config_tindakan', function (Blueprint $table) {
                    $table->foreign('config_id', 'gpd_rj_cfg_config_fk')
                        ->references('id')
                        ->on('generate_premi_dokter_configs')
                        ->cascadeOnDelete();
                });
            }

            if (! $this->foreignKeyExists('generate_premi_dokter_rawat_jalan_config_tindakan', 'gpd_rj_cfg_tindakan_jns_fk')) {
                Schema::table('generate_premi_dokter_rawat_jalan_config_tindakan', function (Blueprint $table) {
                    $table->foreign('jnsTindakan_id', 'gpd_rj_cfg_tindakan_jns_fk')
                        ->references('id')
                        ->on('master_jenis_tindakan')
                        ->cascadeOnDelete();
                });
            }

            if (! $this->indexExists('generate_premi_dokter_rawat_jalan_config_tindakan', 'gpd_rj_cfg_tindakan_unique')) {
                Schema::table('generate_premi_dokter_rawat_jalan_config_tindakan', function (Blueprint $table) {
                    $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_rj_cfg_tindakan_unique');
                });
            }

            if (! $this->indexExists('generate_premi_dokter_rawat_jalan_config_tindakan', 'gpd_rj_cfg_tindakan_config_idx')) {
                Schema::table('generate_premi_dokter_rawat_jalan_config_tindakan', function (Blueprint $table) {
                    $table->index('config_id', 'gpd_rj_cfg_tindakan_config_idx');
                });
            }
        }

        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasTable('generate_premi_dokter_rawat_jalan_special_doctor')) {
            Schema::create('generate_premi_dokter_rawat_jalan_special_doctor', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('config_id');
                $table->string('group_key', 40);
                $table->string('kd_dokter', 30);
                $table->string('nm_dokter');
                $table->string('kd_sps', 20)->nullable();
                $table->string('nm_sps')->nullable();
                $table->unsignedBigInteger('nominal')->default(0);
                $table->timestamps();

                $table->foreign('config_id', 'gpd_rj_special_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
                $table->unique(['config_id', 'group_key', 'kd_dokter'], 'gpd_rj_special_doctor_unique');
                $table->index(['config_id', 'group_key'], 'gpd_rj_special_doctor_group_idx');
            });
        }

        if (Schema::hasTable('generate_premi_dokter_rawat_jalan_special_doctor')
            && ! $this->foreignKeyExists('generate_premi_dokter_rawat_jalan_special_doctor', 'gpd_rj_special_config_fk')) {
            Schema::table('generate_premi_dokter_rawat_jalan_special_doctor', function (Blueprint $table) {
                $table->foreign('config_id', 'gpd_rj_special_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('generate_premi_dokter_rawat_jalan_special_doctor')) {
            if (! $this->indexExists('generate_premi_dokter_rawat_jalan_special_doctor', 'gpd_rj_special_doctor_unique')) {
                Schema::table('generate_premi_dokter_rawat_jalan_special_doctor', function (Blueprint $table) {
                    $table->unique(['config_id', 'group_key', 'kd_dokter'], 'gpd_rj_special_doctor_unique');
                });
            }

            if (! $this->indexExists('generate_premi_dokter_rawat_jalan_special_doctor', 'gpd_rj_special_doctor_group_idx')) {
                Schema::table('generate_premi_dokter_rawat_jalan_special_doctor', function (Blueprint $table) {
                    $table->index(['config_id', 'group_key'], 'gpd_rj_special_doctor_group_idx');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_dokter_rawat_jalan_special_doctor');
        Schema::dropIfExists('generate_premi_dokter_rawat_jalan_config_tindakan');
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
