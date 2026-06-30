<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_tindakan_medis_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jnsPremi_id')
                ->nullable()
                ->constrained('master_jenis_premi')
                ->nullOnDelete();
            $table->string('bpjs_source_mode', 20)->default('previous');
            $table->string('distribution_mode', 30)->default('split_evenly');
            $table->boolean('ignore_icu')->default(true);
            $table->boolean('ignore_nicu')->default(true);
            $table->timestamps();
        });

        Schema::create('generate_tindakan_medis_config_source', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_tindakan_medis_configs')
                ->cascadeOnDelete();
            $table->string('source_pattern', 40);
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['config_id', 'source_pattern', 'jnsTindakan_id'],
                'gtm_config_source_unique'
            );
            $table->index('jnsTindakan_id', 'gtm_config_source_tindakan_index');
        });

        Schema::create('generate_tindakan_medis', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('source_periode', 7);
            $table->date('source_tgl_awal');
            $table->date('source_tgl_akhir');
            $table->string('jenis_pelayanan', 10);
            $table->foreignId('jnsPremi_id')
                ->constrained('master_jenis_premi')
                ->restrictOnDelete();
            $table->string('kode_premi', 50)->nullable();
            $table->string('nama_premi')->nullable();
            $table->foreignId('ugd_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('ugd_kode_ploting')->nullable();
            $table->string('ugd_nama_ploting')->nullable();
            $table->foreignId('vk_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('vk_kode_ploting')->nullable();
            $table->string('vk_nama_ploting')->nullable();
            $table->string('bpjs_source_mode', 20)->default('previous');
            $table->boolean('ignore_icu')->default(true);
            $table->boolean('ignore_nicu')->default(true);
            $table->unsignedInteger('jumlah_transaksi')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedInteger('jumlah_jenis_tindakan')->default(0);
            $table->unsignedInteger('jumlah_mapping_premi')->default(0);
            $table->unsignedInteger('jumlah_terabaikan_icu')->default(0);
            $table->unsignedInteger('jumlah_terabaikan_nicu')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('total_mapping_premi', 20, 2)->default(0);
            $table->decimal('total_ugd', 20, 2)->default(0);
            $table->decimal('total_vk', 20, 2)->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);
            $table->unsignedInteger('pembagi')->default(1);
            $table->decimal('total_final', 20, 2)->default(0);
            $table->string('distribution_mode', 30)->default('split_evenly');
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->decimal('total_dasar_dibagikan', 20, 2)->default(0);
            $table->decimal('total_tambahan_icu', 20, 2)->default(0);
            $table->decimal('total_tambahan_nicu', 20, 2)->default(0);
            $table->decimal('total_dibagikan', 20, 2)->default(0);
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
                ['periode', 'jenis_pelayanan', 'jnsPremi_id'],
                'gtm_period_type_premi_unique'
            );
            $table->index(['periode', 'jenis_pelayanan'], 'gtm_period_type_index');
            $table->index('source_periode', 'gtm_source_period_index');
        });

        Schema::create('generate_tindakan_medis_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_tindakan_medis_id');
            $table->unsignedBigInteger('mapping_premi_id')->nullable();
            $table->foreignId('jnsPremi_id')
                ->constrained('master_jenis_premi')
                ->restrictOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->restrictOnDelete();
            $table->string('kode_premi', 50)->nullable();
            $table->string('nama_premi')->nullable();
            $table->string('kode_jenis_tindakan', 50)->nullable();
            $table->string('nama_jenis_tindakan');
            $table->string('jenis_mapping', 20);
            $table->decimal('nilai_mapping', 20, 4)->default(0);
            $table->json('source_rules')->nullable();
            $table->unsignedInteger('jumlah_data')->default(0);
            $table->unsignedInteger('jumlah_data_icu')->default(0);
            $table->unsignedInteger('jumlah_data_nicu')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('dasar_hitung', 20, 2)->default(0);
            $table->decimal('hasil_mapping', 20, 2)->default(0);
            $table->json('data_rawat');
            $table->timestamps();

            $table->foreign('generate_tindakan_medis_id', 'gtm_detail_header_fk')
                ->references('id')
                ->on('generate_tindakan_medis')
                ->cascadeOnDelete();
            $table->foreign('mapping_premi_id', 'gtm_detail_mapping_fk')
                ->references('id')
                ->on('mapping_premi')
                ->nullOnDelete();
            $table->index(
                ['generate_tindakan_medis_id', 'jnsTindakan_id'],
                'gtm_detail_tindakan_index'
            );
            $table->index('mapping_premi_id', 'gtm_detail_mapping_index');
        });

        Schema::create('generate_tindakan_medis_distribution', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_tindakan_medis_id');
            $table->foreignId('jnsPremi_id')
                ->constrained('master_jenis_premi')
                ->restrictOnDelete();
            $table->string('nik', 50)->index();
            $table->string('pegawai_name', 150);
            $table->string('pegawai_position', 150)->nullable();
            $table->string('distribution_mode', 30)->default('split_evenly');
            $table->decimal('total_final', 20, 2)->default(0);
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->decimal('total_dasar', 20, 2)->default(0);
            $table->boolean('has_icu_bonus')->default(false);
            $table->decimal('total_icu', 20, 2)->default(0);
            $table->json('icu_bonus_info')->nullable();
            $table->boolean('has_nicu_bonus')->default(false);
            $table->decimal('total_nicu', 20, 2)->default(0);
            $table->json('nicu_bonus_info')->nullable();
            $table->decimal('total_diterima', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('generate_tindakan_medis_id', 'gtm_distribution_header_fk')
                ->references('id')
                ->on('generate_tindakan_medis')
                ->cascadeOnDelete();

            $table->unique(
                ['generate_tindakan_medis_id', 'nik'],
                'gtm_distribution_header_nik_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_tindakan_medis_distribution');
        Schema::dropIfExists('generate_tindakan_medis_detail');
        Schema::dropIfExists('generate_tindakan_medis');
        Schema::dropIfExists('generate_tindakan_medis_config_source');
        Schema::dropIfExists('generate_tindakan_medis_configs');
    }
};
