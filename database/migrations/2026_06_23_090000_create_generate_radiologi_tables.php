<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_radiologi_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_radiologi', 10)->unique();
            $table->string('petugas_mode', 10)->default('percent');
            $table->decimal('petugas_percent', 8, 4)->default(25);
            $table->unsignedBigInteger('petugas_nominal')->default(0);
            $table->string('bersama_mode', 10)->default('source');
            $table->decimal('bersama_percent', 8, 4)->default(100);
            $table->unsignedBigInteger('bersama_nominal')->default(0);
            $table->timestamps();
        });

        Schema::create('generate_radiologi_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_radiologi_configs')
                ->cascadeOnDelete();
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'pegawai_id'], 'generate_radiologi_config_pegawai_unique');
            $table->index('pegawai_id');
        });

        Schema::create('generate_radiologi', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_radiologi', 10);
            $table->unsignedInteger('jumlah_tindakan')->default(0);
            $table->unsignedInteger('jumlah_pasien')->default(0);
            $table->unsignedBigInteger('total_tarif_tindakan_petugas')->default(0);
            $table->unsignedBigInteger('total_biaya')->default(0);
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

            $table->unique(['periode', 'jenis_radiologi'], 'generate_radiologi_period_type_unique');
            $table->index(['periode', 'jenis_radiologi']);
        });

        Schema::create('generate_radiologi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_radiologi_id')
                ->constrained('generate_radiologi')
                ->cascadeOnDelete();
            $table->string('role', 30)->default('petugas');
            $table->string('role_label')->default('Petugas Radiologi');
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_radiologi_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_radiologi_detail');
        Schema::dropIfExists('generate_radiologi');
        Schema::dropIfExists('generate_radiologi_config_pegawai');
        Schema::dropIfExists('generate_radiologi_configs');
    }
};
