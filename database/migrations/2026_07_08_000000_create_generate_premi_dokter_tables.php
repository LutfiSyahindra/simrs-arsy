<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_premi_dokter_configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('visite_umum_percent', 8, 4)->default(50);
            $table->decimal('visite_bpjs_percent', 8, 4)->default(50);
            $table->unsignedBigInteger('visite_bpjs_nominal')->default(0);
            $table->timestamps();
        });

        Schema::create('generate_premi_dokter_config_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_premi_dokter_configs')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('jnsTindakan_id');
            $table->timestamps();

            $table->foreign('jnsTindakan_id', 'gpd_cfg_tindakan_jns_fk')
                ->references('id')
                ->on('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->index('config_id', 'gpd_cfg_tindakan_config_index');
            $table->unique(['config_id', 'jnsTindakan_id'], 'gpd_cfg_tindakan_unique');
        });

        Schema::create('generate_premi_dokter_config_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_premi_dokter_configs')
                ->cascadeOnDelete();
            $table->string('kategori', 30);
            $table->string('kd_dokter', 30);
            $table->string('nm_dokter');
            $table->string('kd_sps', 20)->nullable();
            $table->string('nm_sps')->nullable();
            $table->decimal('percent', 8, 4)->default(50);
            $table->timestamps();

            $table->unique(['config_id', 'kd_dokter'], 'gpd_cfg_doctor_unique');
            $table->index(['config_id', 'kategori'], 'gpd_cfg_doctor_category_index');
            $table->index('kd_dokter', 'gpd_cfg_doctor_code_index');
        });

        Schema::create('generate_premi_dokter', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('source_periode', 7);
            $table->date('source_tgl_awal');
            $table->date('source_tgl_akhir');
            $table->string('jenis_premi_dokter', 30)->default('visite');
            $table->string('jenis_pelayanan', 10);
            $table->decimal('visite_umum_percent', 8, 4)->default(50);
            $table->decimal('visite_bpjs_percent', 8, 4)->default(50);
            $table->unsignedBigInteger('visite_bpjs_nominal')->default(0);
            $table->unsignedInteger('jumlah_transaksi')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedInteger('jumlah_dokter')->default(0);
            $table->unsignedInteger('jumlah_tindakan')->default(0);
            $table->unsignedInteger('jumlah_mapping_tindakan')->default(0);
            $table->unsignedInteger('jumlah_tidak_terkonfigurasi')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('total_grand', 20, 2)->default(0);
            $table->decimal('total_premi', 20, 2)->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('generate_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['periode', 'jenis_premi_dokter', 'jenis_pelayanan'],
                'gpd_period_type_service_unique'
            );
            $table->index(['periode', 'jenis_pelayanan'], 'gpd_period_service_index');
        });

        Schema::create('generate_premi_dokter_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_premi_dokter_id');
            $table->string('kd_dokter', 30);
            $table->string('nm_dokter');
            $table->string('kd_sps', 20)->nullable();
            $table->string('nm_sps')->nullable();
            $table->string('kategori', 30);
            $table->decimal('percent', 8, 4)->default(50);
            $table->unsignedInteger('jumlah_data')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);
            $table->decimal('total_premi', 20, 2)->default(0);
            $table->json('source_breakdown')->nullable();
            $table->json('action_breakdown')->nullable();
            $table->json('data_rawat');
            $table->timestamps();

            $table->foreign('generate_premi_dokter_id', 'gpd_detail_header_fk')
                ->references('id')
                ->on('generate_premi_dokter')
                ->cascadeOnDelete();
            $table->unique(
                ['generate_premi_dokter_id', 'kd_dokter'],
                'gpd_detail_header_doctor_unique'
            );
            $table->index('kd_dokter', 'gpd_detail_doctor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_dokter_detail');
        Schema::dropIfExists('generate_premi_dokter');
        Schema::dropIfExists('generate_premi_dokter_config_doctor');
        Schema::dropIfExists('generate_premi_dokter_config_tindakan');
        Schema::dropIfExists('generate_premi_dokter_configs');
    }
};
