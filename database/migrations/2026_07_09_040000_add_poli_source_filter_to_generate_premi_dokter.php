<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasTable('generate_premi_dokter_poli_filter_doctor')) {
            Schema::create('generate_premi_dokter_poli_filter_doctor', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('config_id');
                $table->string('kd_dokter', 30);
                $table->string('nm_dokter', 100);
                $table->string('kd_sps', 10)->nullable();
                $table->string('nm_sps', 100)->nullable();
                $table->timestamps();

                $table->foreign('config_id', 'gpd_poli_filter_doctor_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
                $table->unique(['config_id', 'kd_dokter'], 'gpd_poli_filter_doctor_unique');
                $table->index('config_id', 'gpd_poli_filter_doctor_config_idx');
            });
        }

        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasTable('generate_premi_dokter_poli_filter_tindakan')) {
            Schema::create('generate_premi_dokter_poli_filter_tindakan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('config_id');
                $table->unsignedBigInteger('jnsTindakan_id');
                $table->timestamps();

                $table->foreign('config_id', 'gpd_poli_filter_tindakan_config_fk')
                    ->references('id')
                    ->on('generate_premi_dokter_configs')
                    ->cascadeOnDelete();
                $table->foreign('jnsTindakan_id', 'gpd_poli_filter_tindakan_jns_fk')
                    ->references('id')
                    ->on('master_jenis_tindakan')
                    ->cascadeOnDelete();
                $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_poli_filter_tindakan_unique');
                $table->index('config_id', 'gpd_poli_filter_tindakan_config_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_dokter_poli_filter_tindakan');
        Schema::dropIfExists('generate_premi_dokter_poli_filter_doctor');
    }
};
