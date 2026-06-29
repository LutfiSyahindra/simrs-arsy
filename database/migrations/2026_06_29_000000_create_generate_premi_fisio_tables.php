<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_premi_fisio_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_fisio', 10)->unique();
            $table->string('grand_mode', 20)->default('tindakan');
            $table->unsignedBigInteger('grand_nominal')->default(0);
            $table->string('petugas1_mode', 10)->default('percent');
            $table->decimal('petugas1_percent', 8, 4)->default(50);
            $table->unsignedBigInteger('petugas1_nominal')->default(0);
            $table->string('petugas2_mode', 10)->default('percent');
            $table->decimal('petugas2_percent', 8, 4)->default(50);
            $table->unsignedBigInteger('petugas2_nominal')->default(0);
            $table->string('bersama_mode', 10)->default('percent');
            $table->decimal('bersama_percent', 8, 4)->default(50);
            $table->unsignedBigInteger('bersama_nominal')->default(0);
            $table->timestamps();
        });

        Schema::create('generate_premi_fisio_config_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tindakan', 40)->nullable();
            $table->string('nama_tindakan');
            $table->unsignedBigInteger('harga')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('nama_tindakan');
        });

        Schema::create('generate_premi_fisio_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_premi_fisio_configs')
                ->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'role', 'pegawai_id'], 'generate_premi_fisio_config_pegawai_unique');
            $table->index(['role', 'pegawai_id']);
        });

        Schema::create('generate_premi_fisio', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_fisio', 10);
            $table->string('kode_generate', 80);
            $table->foreignId('tindakan_config_id')
                ->nullable()
                ->constrained('generate_premi_fisio_config_tindakan')
                ->nullOnDelete();
            $table->string('nama_tindakan')->nullable();
            $table->unsignedBigInteger('harga_tindakan')->default(0);
            $table->unsignedInteger('jumlah_tindakan')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('total_petugas1')->default(0);
            $table->unsignedBigInteger('total_petugas2')->default(0);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_dibagikan')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'jenis_fisio', 'kode_generate'], 'generate_premi_fisio_period_type_key_unique');
            $table->index(['periode', 'jenis_fisio']);
            $table->index('kode_generate');
        });

        Schema::create('generate_premi_fisio_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_premi_fisio_id')
                ->constrained('generate_premi_fisio')
                ->cascadeOnDelete();
            $table->string('detail_type', 20);
            $table->string('source_label')->nullable();
            $table->foreignId('tindakan_config_id')
                ->nullable()
                ->constrained('generate_premi_fisio_config_tindakan')
                ->nullOnDelete();
            $table->string('nama_tindakan')->nullable();
            $table->unsignedBigInteger('harga_tindakan')->default(0);
            $table->unsignedInteger('jumlah')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->string('role', 20)->nullable();
            $table->string('role_label')->nullable();
            $table->string('pegawai_id', 30)->nullable();
            $table->string('pegawai_name')->nullable();
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('basis_amount')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_premi_fisio_id', 'detail_type'], 'gp_fisio_detail_type_idx');
            $table->index(['role', 'pegawai_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_fisio_detail');
        Schema::dropIfExists('generate_premi_fisio');
        Schema::dropIfExists('generate_premi_fisio_config_pegawai');
        Schema::dropIfExists('generate_premi_fisio_config_tindakan');
        Schema::dropIfExists('generate_premi_fisio_configs');
    }
};
