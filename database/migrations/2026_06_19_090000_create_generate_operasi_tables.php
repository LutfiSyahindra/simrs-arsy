<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_operasi_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_operasi', 10)->unique();
            $table->decimal('instrumen_percent', 8, 2)->default(10);
            $table->decimal('instrumen_premi_bersama_percent', 8, 2)->default(20);
            $table->decimal('instrumen_petugas_percent', 8, 2)->default(80);
            $table->decimal('instrumen_petugas_kelompok_20_percent', 8, 2)->default(20);
            $table->decimal('instrumen_petugas_kelompok_80_percent', 8, 2)->default(80);
            $table->decimal('dokter_anastesi_percent', 8, 2)->default(40);
            $table->decimal('perawat_anastesi_percent', 8, 2)->default(10);
            $table->timestamps();
        });

        Schema::create('generate_operasi_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_operasi_configs')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('pegawai_source', 20);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(
                ['config_id', 'role', 'pegawai_source', 'pegawai_id'],
                'generate_operasi_config_role_pegawai_unique'
            );
            $table->index(['role', 'pegawai_source']);
        });

        Schema::create('generate_operasi', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_operasi', 10);
            $table->unsignedBigInteger('total_operasi');
            $table->unsignedBigInteger('total_instrumen')->default(0);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_instrumen_petugas')->default(0);
            $table->unsignedBigInteger('total_instrumen_kelompok_20')->default(0);
            $table->unsignedBigInteger('total_instrumen_kelompok_80')->default(0);
            $table->unsignedBigInteger('total_dokter_anastesi')->default(0);
            $table->unsignedBigInteger('total_perawat_anastesi')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'jenis_operasi'], 'generate_operasi_period_type_unique');
            $table->index(['periode', 'jenis_operasi']);
        });

        Schema::create('generate_operasi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_operasi_id')
                ->constrained('generate_operasi')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('role_label');
            $table->string('pegawai_source', 20);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_operasi_id', 'role']);
            $table->index(['pegawai_source', 'pegawai_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_operasi_details');
        Schema::dropIfExists('generate_operasi');
        Schema::dropIfExists('generate_operasi_config_pegawai');
        Schema::dropIfExists('generate_operasi_configs');
    }
};
