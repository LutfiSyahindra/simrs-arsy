<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_laboratorium_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_laboratorium', 10)->unique();
            $table->string('petugas_mode', 20)->default('percent');
            $table->decimal('petugas_percent', 8, 4)->default(25);
            $table->unsignedBigInteger('petugas_nominal')->default(0);
            $table->unsignedInteger('bpjs_petugas_divider')->default(7);
            $table->string('bersama_mode', 20)->default('source');
            $table->decimal('bersama_percent', 8, 4)->default(100);
            $table->unsignedBigInteger('bersama_nominal')->default(0);
            $table->timestamps();
        });

        Schema::create('generate_laboratorium_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_laboratorium_configs')
                ->cascadeOnDelete();
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'pegawai_id'], 'generate_laboratorium_config_pegawai_unique');
            $table->index('pegawai_id');
        });

        Schema::create('generate_laboratorium', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_laboratorium', 10);
            $table->unsignedInteger('jumlah_tindakan')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedBigInteger('total_bagian_laborat')->default(0);
            $table->unsignedBigInteger('total_bagian_rs')->default(0);
            $table->unsignedBigInteger('total_manajemen')->default(0);
            $table->unsignedBigInteger('total_premi_petugas')->default(0);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->unsignedBigInteger('total_dibagikan')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'jenis_laboratorium'], 'generate_laboratorium_period_type_unique');
            $table->index(['periode', 'jenis_laboratorium']);
        });

        Schema::create('generate_laboratorium_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_laboratorium_id')
                ->constrained('generate_laboratorium')
                ->cascadeOnDelete();
            $table->string('role', 30)->default('petugas');
            $table->string('role_label')->default('Petugas Laboratorium');
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_laboratorium_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_laboratorium_detail');
        Schema::dropIfExists('generate_laboratorium');
        Schema::dropIfExists('generate_laboratorium_config_pegawai');
        Schema::dropIfExists('generate_laboratorium_configs');
    }
};
