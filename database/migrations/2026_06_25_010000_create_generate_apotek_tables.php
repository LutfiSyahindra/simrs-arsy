<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_apotek_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_apotek', 10)->unique();
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->unsignedInteger('tarif_per_item')->default(500);
            $table->string('source_period_mode', 20)->default('previous');
            $table->decimal('jasa_farmasi_percent', 8, 4)->default(50);
            $table->decimal('formula_31_percent', 8, 4)->default(31);
            $table->decimal('formula_31_divider', 8, 2)->default(2.5);
            $table->decimal('formula_7_percent', 8, 4)->default(7);
            $table->decimal('formula_7_divider', 8, 2)->default(1);
            $table->decimal('formula_12_percent', 8, 4)->default(12);
            $table->decimal('formula_12_divider', 8, 2)->default(2);
            $table->decimal('premi_bersama_percent', 8, 4)->default(30);
            $table->timestamps();
        });

        Schema::create('generate_apotek', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('source_periode', 7);
            $table->date('source_tgl_awal');
            $table->date('source_tgl_akhir');
            $table->string('jenis_apotek', 10);
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->string('kode_jenis_tindakan', 30)->nullable();
            $table->string('nama_jenis_tindakan')->nullable();
            $table->unsignedInteger('jumlah_data_sumber')->default(0);
            $table->unsignedInteger('jumlah_pasien_sumber')->default(0);
            $table->unsignedInteger('jumlah_obat_sumber')->default(0);
            $table->unsignedInteger('jumlah_data_mapping')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedInteger('jumlah_obat')->default(0);
            $table->decimal('total_qty', 14, 2)->default(0);
            $table->unsignedInteger('tarif_per_item')->default(500);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('total_jasa_farmasi_pool')->default(0);
            $table->unsignedBigInteger('total_formula_31')->default(0);
            $table->unsignedBigInteger('total_formula_7')->default(0);
            $table->unsignedBigInteger('total_formula_12')->default(0);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_dibagikan')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'jenis_apotek'], 'generate_apotek_period_type_unique');
            $table->index(['periode', 'jenis_apotek']);
            $table->index('source_periode');
        });

        Schema::create('generate_apotek_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_apotek_id')
                ->constrained('generate_apotek')
                ->cascadeOnDelete();
            $table->foreignId('mapping_tindakan_id')
                ->nullable()
                ->constrained('mapping_tindakan')
                ->nullOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->nullable()
                ->constrained('master_jenis_tindakan')
                ->nullOnDelete();
            $table->string('source_table', 40)->default('detail_pemberian_obat');
            $table->string('sumber_tindakan', 20)->default('FARMASI');
            $table->string('no_rawat', 30);
            $table->string('no_rkm_medis', 20)->nullable();
            $table->string('nm_pasien')->nullable();
            $table->string('kd_pj', 10)->nullable();
            $table->string('nama_penjamin')->nullable();
            $table->date('tanggal');
            $table->time('jam')->nullable();
            $table->string('kode_barang', 80);
            $table->string('nama_barang')->nullable();
            $table->decimal('qty', 14, 2)->default(0);
            $table->unsignedBigInteger('harga_obat')->default(0);
            $table->unsignedBigInteger('total_obat')->default(0);
            $table->unsignedInteger('nominal_premi')->default(500);
            $table->unsignedBigInteger('total_premi')->default(0);
            $table->string('status', 30)->nullable();
            $table->timestamps();

            $table->index(['generate_apotek_id', 'tanggal']);
            $table->index(['no_rawat', 'tanggal']);
            $table->index(['sumber_tindakan', 'kode_barang']);
        });

        Schema::create('generate_apotek_config_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_apotek_configs')
                ->cascadeOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['config_id', 'jnsTindakan_id'], 'generate_apotek_config_mapping_unique');
            $table->index('jnsTindakan_id');
        });

        Schema::create('generate_apotek_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_apotek_configs')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'role', 'pegawai_id'], 'generate_apotek_config_pegawai_unique');
            $table->index(['role', 'pegawai_id']);
        });

        Schema::create('generate_apotek_recipient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_apotek_id')
                ->constrained('generate_apotek')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('role_label');
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->decimal('divider', 8, 2)->default(1);
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('amount_per_recipient')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_apotek_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_apotek_recipient');
        Schema::dropIfExists('generate_apotek_config_pegawai');
        Schema::dropIfExists('generate_apotek_config_mapping');
        Schema::dropIfExists('generate_apotek_detail');
        Schema::dropIfExists('generate_apotek');
        Schema::dropIfExists('generate_apotek_configs');
    }
};
