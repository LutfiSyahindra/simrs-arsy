<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_premi_bersama_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jnsPremi_umum_id')
                ->nullable()
                ->constrained('master_jenis_premi')
                ->nullOnDelete();
            $table->foreignId('jnsPremi_bpjs_id')
                ->nullable()
                ->constrained('master_jenis_premi')
                ->nullOnDelete();
            $table->foreignId('ugd_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->foreignId('vk_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->foreignId('kamar_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->foreignId('bhp_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('bpjs_source_mode', 20)->default('previous');
            $table->boolean('ignore_icu')->default(true);
            $table->boolean('ignore_nicu')->default(true);
            $table->timestamps();
        });

        Schema::create('generate_premi_bersama_config_source', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_premi_bersama_configs')
                ->cascadeOnDelete();
            $table->string('source_pattern', 40);
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['config_id', 'source_pattern', 'jnsTindakan_id'],
                'gpb_config_source_unique'
            );
            $table->index('jnsTindakan_id', 'gpb_config_source_tindakan_index');
        });

        Schema::create('generate_premi_bersama_config_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_premi_bersama_configs')
                ->cascadeOnDelete();
            $table->string('kd_dokter', 30);
            $table->string('nm_dokter');
            $table->timestamps();

            $table->unique(['config_id', 'kd_dokter'], 'gpb_config_doctor_unique');
            $table->index('kd_dokter', 'gpb_config_doctor_code_index');
        });

        Schema::create('generate_premi_bersama_config_doctor_action', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('config_id');
            $table->unsignedBigInteger('jnsTindakan_id');
            $table->timestamps();

            $table->foreign('config_id', 'gpb_doc_action_config_fk')
                ->references('id')
                ->on('generate_premi_bersama_configs')
                ->cascadeOnDelete();
            $table->foreign('jnsTindakan_id', 'gpb_doc_action_tindakan_fk')
                ->references('id')
                ->on('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->unique(
                ['config_id', 'jnsTindakan_id'],
                'gpb_config_doctor_action_unique'
            );
        });

        Schema::create('generate_premi_bersama_config_include_action', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('config_id');
            $table->string('jenis_pelayanan', 10)->default('umum');
            $table->unsignedBigInteger('jnsTindakan_id');
            $table->timestamps();

            $table->foreign('config_id', 'gpb_include_config_fk')
                ->references('id')
                ->on('generate_premi_bersama_configs')
                ->cascadeOnDelete();
            $table->foreign('jnsTindakan_id', 'gpb_include_tindakan_fk')
                ->references('id')
                ->on('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->unique(
                ['config_id', 'jenis_pelayanan', 'jnsTindakan_id'],
                'gpb_config_include_action_unique'
            );
            $table->index(['jenis_pelayanan', 'jnsTindakan_id'], 'gpb_config_include_type_action_index');
        });

        Schema::create('generate_premi_bersama', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('source_periode', 7);
            $table->string('bpjs_source_mode', 20)->default('previous');
            $table->string('bpjs_source_periode', 7)->nullable();
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
            $table->foreignId('kamar_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('kamar_kode_ploting')->nullable();
            $table->string('kamar_nama_ploting')->nullable();
            $table->foreignId('bhp_plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('bhp_kode_ploting')->nullable();
            $table->string('bhp_nama_ploting')->nullable();
            $table->boolean('ignore_icu')->default(true);
            $table->boolean('ignore_nicu')->default(true);
            $table->unsignedInteger('jumlah_transaksi')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedInteger('jumlah_jenis_tindakan')->default(0);
            $table->unsignedInteger('jumlah_mapping_premi')->default(0);
            $table->unsignedInteger('jumlah_terabaikan_icu')->default(0);
            $table->unsignedInteger('jumlah_terabaikan_nicu')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('total_tindakan_rawat', 20, 2)->default(0);
            $table->unsignedInteger('jumlah_sumber_generator')->default(0);
            $table->unsignedInteger('jumlah_sumber_terkunci')->default(0);
            $table->decimal('total_generator_sumber', 20, 2)->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->decimal('total_skor', 20, 2)->default(0);
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
                'gpb_period_type_premi_unique'
            );
            $table->index(['periode', 'jenis_pelayanan'], 'gpb_period_type_index');
            $table->index('source_periode', 'gpb_source_period_index');
        });

        Schema::create('generate_premi_bersama_source', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_premi_bersama_id');
            $table->string('source_key', 60);
            $table->string('source_label');
            $table->string('source_table', 80);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_periode', 7)->nullable();
            $table->string('source_type', 20)->default('umum');
            $table->foreignId('plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('kode_ploting')->nullable();
            $table->string('nama_ploting')->nullable();
            $table->unsignedInteger('jumlah_data')->default(0);
            $table->decimal('total_asal', 20, 2)->default(0);
            $table->decimal('total_diambil', 20, 2)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->string('status_label', 40)->nullable();
            $table->text('note')->nullable();
            $table->json('raw_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('generate_premi_bersama_id', 'gpb_source_header_fk')
                ->references('id')
                ->on('generate_premi_bersama')
                ->cascadeOnDelete();
            $table->index(['generate_premi_bersama_id', 'source_key'], 'gpb_source_header_key_index');
            $table->index(['source_table', 'source_id'], 'gpb_source_table_id_index');
        });

        Schema::create('generate_premi_bersama_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_premi_bersama_id');
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
            $table->json('mapping_snapshot')->nullable();
            $table->unsignedInteger('jumlah_data')->default(0);
            $table->unsignedInteger('jumlah_data_icu')->default(0);
            $table->unsignedInteger('jumlah_data_nicu')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('dasar_hitung', 20, 2)->default(0);
            $table->decimal('hasil_mapping', 20, 2)->default(0);
            $table->json('data_rawat');
            $table->timestamps();

            $table->foreign('generate_premi_bersama_id', 'gpb_detail_header_fk')
                ->references('id')
                ->on('generate_premi_bersama')
                ->cascadeOnDelete();
            $table->foreign('mapping_premi_id', 'gpb_detail_mapping_fk')
                ->references('id')
                ->on('mapping_premi')
                ->nullOnDelete();
            $table->index(
                ['generate_premi_bersama_id', 'jnsTindakan_id'],
                'gpb_detail_tindakan_index'
            );
            $table->index('mapping_premi_id', 'gpb_detail_mapping_index');
        });

        Schema::create('generate_premi_bersama_distribution', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_premi_bersama_id');
            $table->foreignId('jnsPremi_id')
                ->constrained('master_jenis_premi')
                ->restrictOnDelete();
            $table->string('nik', 50)->index();
            $table->string('pegawai_name', 150);
            $table->string('pegawai_position', 150)->nullable();
            $table->string('stts_kerja', 20)->nullable();
            $table->decimal('skor_pegawai', 20, 2)->default(0);
            $table->decimal('total_skor', 20, 2)->default(0);
            $table->decimal('allocation_percent', 8, 4)->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);
            $table->decimal('total_received', 20, 2)->default(0);
            $table->json('skor_detail')->nullable();
            $table->timestamps();

            $table->foreign('generate_premi_bersama_id', 'gpb_distribution_header_fk')
                ->references('id')
                ->on('generate_premi_bersama')
                ->cascadeOnDelete();
            $table->unique(
                ['generate_premi_bersama_id', 'nik'],
                'gpb_distribution_header_nik_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_bersama_distribution');
        Schema::dropIfExists('generate_premi_bersama_detail');
        Schema::dropIfExists('generate_premi_bersama_source');
        Schema::dropIfExists('generate_premi_bersama');
        Schema::dropIfExists('generate_premi_bersama_config_include_action');
        Schema::dropIfExists('generate_premi_bersama_config_doctor_action');
        Schema::dropIfExists('generate_premi_bersama_config_doctor');
        Schema::dropIfExists('generate_premi_bersama_config_source');
        Schema::dropIfExists('generate_premi_bersama_configs');
    }
};
