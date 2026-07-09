<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                if (! Schema::hasColumn('generate_premi_dokter_configs', 'poli_percent')) {
                    $table->decimal('poli_percent', 8, 4)->default(30)->after('kebersamaan_divider');
                }

                if (! Schema::hasColumn('generate_premi_dokter_configs', 'poli_distribution_mode')) {
                    $table->string('poli_distribution_mode', 30)->default('split_evenly')->after('poli_percent');
                }

                if (! Schema::hasColumn('generate_premi_dokter_configs', 'konsul_wa_nominal')) {
                    $table->unsignedBigInteger('konsul_wa_nominal')->default(0)->after('poli_distribution_mode');
                }
            });
        }

        $this->createMappingTableIfMissing(
            'generate_premi_dokter_poli_config_tindakan',
            'gpd_poli_cfg_config_fk',
            'gpd_poli_cfg_tindakan_jns_fk',
            'gpd_poli_cfg_tindakan_unique',
            'gpd_poli_cfg_tindakan_config_idx'
        );

        $this->createMappingTableIfMissing(
            'generate_premi_dokter_konsul_wa_config_tindakan',
            'gpd_kwa_cfg_config_fk',
            'gpd_kwa_cfg_tindakan_jns_fk',
            'gpd_kwa_cfg_tindakan_unique',
            'gpd_kwa_cfg_tindakan_config_idx'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_dokter_konsul_wa_config_tindakan');
        Schema::dropIfExists('generate_premi_dokter_poli_config_tindakan');

        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                if (Schema::hasColumn('generate_premi_dokter_configs', 'konsul_wa_nominal')) {
                    $table->dropColumn('konsul_wa_nominal');
                }

                if (Schema::hasColumn('generate_premi_dokter_configs', 'poli_distribution_mode')) {
                    $table->dropColumn('poli_distribution_mode');
                }

                if (Schema::hasColumn('generate_premi_dokter_configs', 'poli_percent')) {
                    $table->dropColumn('poli_percent');
                }
            });
        }
    }

    private function createMappingTableIfMissing(
        string $tableName,
        string $configFk,
        string $tindakanFk,
        string $uniqueIndex,
        string $configIndex
    ): void {
        if (! Schema::hasTable('generate_premi_dokter_configs')
            || Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use (
            $configFk,
            $tindakanFk,
            $uniqueIndex,
            $configIndex
        ) {
            $table->id();
            $table->unsignedBigInteger('config_id');
            $table->unsignedBigInteger('jnsTindakan_id');
            $table->timestamps();

            $table->foreign('config_id', $configFk)
                ->references('id')
                ->on('generate_premi_dokter_configs')
                ->cascadeOnDelete();
            $table->foreign('jnsTindakan_id', $tindakanFk)
                ->references('id')
                ->on('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->unique(['config_id', 'jnsTindakan_id'], $uniqueIndex);
            $table->index('config_id', $configIndex);
        });
    }
};
