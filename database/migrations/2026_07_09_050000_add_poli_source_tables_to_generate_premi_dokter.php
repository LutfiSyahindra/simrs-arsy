<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SOURCE_TABLES = [
        'rawat_jl_pr' => 'Rawat Jalan Paramedis',
        'rawat_inap_pr' => 'Rawat Inap Paramedis',
        'rawat_jl_dr' => 'Rawat Jalan Dokter',
        'rawat_inap_dr' => 'Rawat Inap Dokter',
        'rawat_jl_drpr' => 'Rawat Jalan Dokter & Paramedis',
        'rawat_inap_drpr' => 'Rawat Inap Dokter & Paramedis',
    ];

    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasTable('generate_premi_dokter_poli_filter_source')) {
            Schema::create('generate_premi_dokter_poli_filter_source', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('config_id');
                $table->string('source_table', 60);
                $table->string('source_label', 120);
                $table->timestamps();

                $table->foreign('config_id', 'gpd_poli_filter_source_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
                $table->unique(['config_id', 'source_table'], 'gpd_poli_filter_source_unique');
                $table->index('config_id', 'gpd_poli_filter_source_config_idx');
            });

            $now = now();
            $rows = DB::table('generate_premi_dokter_configs')
                ->pluck('id')
                ->flatMap(fn ($configId) => collect(self::SOURCE_TABLES)->map(fn ($label, $table) => [
                    'config_id' => $configId,
                    'source_table' => $table,
                    'source_label' => $label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]))
                ->values();

            if ($rows->isNotEmpty()) {
                DB::table('generate_premi_dokter_poli_filter_source')->insert($rows->all());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_dokter_poli_filter_source');
    }
};
