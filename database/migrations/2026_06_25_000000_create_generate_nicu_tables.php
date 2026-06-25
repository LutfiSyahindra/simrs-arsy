<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_nicu_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_nicu', 10)->unique();
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->decimal('perawat_nicu_percent', 8, 4)->default(30);
            $table->decimal('pegawai_nicu_khusus_percent', 8, 4)->default(25);
            $table->decimal('perawat_nicu_reguler_percent', 8, 4)->default(75);
            $table->unsignedInteger('perawat_nicu_divider')->default(4);
            $table->decimal('premi_medis_percent', 8, 4)->default(25);
            $table->unsignedInteger('premi_medis_divider')->default(34);
            $table->decimal('premi_bersama_percent', 8, 4)->default(15);
            $table->string('critical_action_name')->nullable();
            $table->json('critical_action_names')->nullable();
            $table->timestamps();
        });

        Schema::create('generate_nicu', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('source_periode', 7);
            $table->date('source_tgl_awal');
            $table->date('source_tgl_akhir');
            $table->string('jenis_nicu', 10);
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->string('kode_jenis_tindakan', 30)->nullable();
            $table->string('nama_jenis_tindakan')->nullable();
            $table->unsignedInteger('jumlah_pasien_sumber')->default(0);
            $table->unsignedInteger('jumlah_pasien_nicu')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedInteger('jumlah_tindakan')->default(0);
            $table->unsignedInteger('jumlah_tindakan_nicu')->default(0);
            $table->unsignedInteger('jumlah_tindakan_kritikal')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('total_perawat_nicu')->default(0);
            $table->unsignedBigInteger('total_perawat_nicu_reguler')->default(0);
            $table->unsignedBigInteger('total_pegawai_nicu_khusus')->default(0);
            $table->unsignedBigInteger('total_premi_medis_pool')->default(0);
            $table->unsignedBigInteger('premi_medis_per_orang')->default(0);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'jenis_nicu'], 'generate_nicu_period_type_unique');
            $table->index(['periode', 'jenis_nicu']);
            $table->index('source_periode');
        });

        Schema::create('generate_nicu_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_nicu_id')
                ->constrained('generate_nicu')
                ->cascadeOnDelete();
            $table->foreignId('mapping_tindakan_id')
                ->nullable()
                ->constrained('mapping_tindakan')
                ->nullOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->string('source_table', 40);
            $table->string('sumber_tindakan', 20);
            $table->string('no_rawat', 30);
            $table->string('no_rkm_medis', 20)->nullable();
            $table->string('nm_pasien')->nullable();
            $table->string('kd_pj', 10)->nullable();
            $table->string('nama_penjamin')->nullable();
            $table->string('kd_kamar_nicu', 40)->nullable();
            $table->date('tgl_masuk_nicu')->nullable();
            $table->time('jam_masuk_nicu')->nullable();
            $table->date('tgl_keluar_nicu')->nullable();
            $table->time('jam_keluar_nicu')->nullable();
            $table->date('tanggal');
            $table->time('jam')->nullable();
            $table->string('kd_tindakan', 80);
            $table->string('nm_tindakan');
            $table->string('kd_dokter', 30)->nullable();
            $table->string('nm_dokter')->nullable();
            $table->string('nip', 30)->nullable();
            $table->string('nama_petugas')->nullable();
            $table->unsignedBigInteger('biaya_rawat')->default(0);
            $table->boolean('is_in_nicu_range')->default(false);
            $table->boolean('is_critical_action')->default(false);
            $table->timestamps();

            $table->index(['generate_nicu_id', 'source_table']);
            $table->index(['no_rawat', 'tanggal']);
            $table->index(['sumber_tindakan', 'kd_tindakan']);
        });

        Schema::create('generate_nicu_config_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_nicu_configs')
                ->cascadeOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['config_id', 'jnsTindakan_id'], 'generate_nicu_config_tindakan_unique');
            $table->index('jnsTindakan_id');
        });

        Schema::create('generate_nicu_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_nicu_configs')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'role', 'pegawai_id'], 'generate_nicu_config_pegawai_unique');
            $table->index(['role', 'pegawai_id']);
        });

        Schema::create('generate_nicu_recipient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_nicu_id')
                ->constrained('generate_nicu')
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

            $table->index(['generate_nicu_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_nicu_recipient');
        Schema::dropIfExists('generate_nicu_config_pegawai');
        Schema::dropIfExists('generate_nicu_config_tindakan');
        Schema::dropIfExists('generate_nicu_detail');
        Schema::dropIfExists('generate_nicu');
        Schema::dropIfExists('generate_nicu_configs');
    }
};

