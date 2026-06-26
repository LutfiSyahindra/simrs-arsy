<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_gizi_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_gizi', 10)->unique();
            $table->string('source_period_mode', 20)->default('current');
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->decimal('konsul_pegawai_percent', 8, 4)->default(50);
            $table->decimal('konsul_premi_bersama_percent', 8, 4)->default(30);
            $table->decimal('diit_petugas_percent', 8, 4)->default(3);
            $table->unsignedInteger('diit_petugas_divider')->default(5);
            $table->decimal('diit_premi_bersama_percent', 8, 4)->default(37);
            $table->boolean('diit_premi_bersama_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('generate_gizi_config_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_gizi_configs')
                ->cascadeOnDelete();
            $table->string('kelompok', 20);
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['config_id', 'kelompok', 'jnsTindakan_id'], 'generate_gizi_config_mapping_unique');
            $table->index(['kelompok', 'jnsTindakan_id']);
        });

        Schema::create('generate_gizi_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_gizi_configs')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'role', 'pegawai_id'], 'generate_gizi_config_pegawai_unique');
            $table->index(['role', 'pegawai_id']);
        });

        Schema::create('generate_gizi', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('source_periode', 7);
            $table->string('source_period_mode', 20)->default('current');
            $table->date('source_tgl_awal');
            $table->date('source_tgl_akhir');
            $table->string('jenis_gizi', 10);
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->string('kode_jenis_tindakan', 30)->nullable();
            $table->string('nama_jenis_tindakan')->nullable();
            $table->unsignedInteger('jumlah_data_sumber')->default(0);
            $table->unsignedInteger('jumlah_pasien_sumber')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedInteger('jumlah_tindakan')->default(0);
            $table->unsignedInteger('jumlah_tindakan_konsul')->default(0);
            $table->unsignedInteger('jumlah_tindakan_diit')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('grand_total_konsul')->default(0);
            $table->unsignedBigInteger('grand_total_diit')->default(0);
            $table->unsignedBigInteger('total_konsul_pegawai')->default(0);
            $table->unsignedBigInteger('total_konsul_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_diit_petugas_pool')->default(0);
            $table->unsignedBigInteger('diit_petugas_per_orang')->default(0);
            $table->unsignedBigInteger('total_diit_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_dibagikan')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'jenis_gizi'], 'generate_gizi_period_type_unique');
            $table->index(['periode', 'jenis_gizi']);
            $table->index('source_periode');
        });

        Schema::create('generate_gizi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_gizi_id')
                ->constrained('generate_gizi')
                ->cascadeOnDelete();
            $table->foreignId('mapping_tindakan_id')
                ->nullable()
                ->constrained('mapping_tindakan')
                ->nullOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->string('kelompok', 20);
            $table->string('source_table', 40);
            $table->string('sumber_tindakan', 20);
            $table->string('no_rawat', 30);
            $table->string('no_rkm_medis', 20)->nullable();
            $table->string('nm_pasien')->nullable();
            $table->string('kd_pj', 10)->nullable();
            $table->string('nama_penjamin')->nullable();
            $table->date('tanggal');
            $table->time('jam')->nullable();
            $table->string('kd_tindakan', 80);
            $table->string('nm_tindakan');
            $table->string('kd_dokter', 30)->nullable();
            $table->string('nm_dokter')->nullable();
            $table->string('nip', 30)->nullable();
            $table->string('nama_petugas')->nullable();
            $table->unsignedBigInteger('biaya_rawat')->default(0);
            $table->timestamps();

            $table->index(['generate_gizi_id', 'kelompok']);
            $table->index(['generate_gizi_id', 'source_table']);
            $table->index(['no_rawat', 'tanggal']);
            $table->index(['sumber_tindakan', 'kd_tindakan']);
        });

        Schema::create('generate_gizi_recipient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_gizi_id')
                ->constrained('generate_gizi')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('role_label');
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_gizi_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_gizi_recipient');
        Schema::dropIfExists('generate_gizi_detail');
        Schema::dropIfExists('generate_gizi');
        Schema::dropIfExists('generate_gizi_config_pegawai');
        Schema::dropIfExists('generate_gizi_config_mapping');
        Schema::dropIfExists('generate_gizi_configs');
    }
};
