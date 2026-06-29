<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_premi_driver_configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('premi_bersama_percent', 8, 4)->default(20);
            $table->timestamps();
        });

        Schema::create('generate_premi_driver_tujuan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama_tujuan');
            $table->unsignedBigInteger('harga');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('generate_premi_driver', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->unsignedInteger('jumlah_tujuan')->default(0);
            $table->unsignedInteger('total_jumlah')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->decimal('premi_pegawai_percent', 8, 4)->default(100);
            $table->unsignedBigInteger('total_premi_pegawai')->default(0);
            $table->decimal('premi_bersama_percent', 8, 4)->default(20);
            $table->unsignedBigInteger('total_premi_bersama')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['periode', 'pegawai_id'], 'generate_premi_driver_period_pegawai_unique');
            $table->index(['periode', 'is_locked']);
        });

        Schema::create('generate_premi_driver_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_premi_driver_id')
                ->constrained('generate_premi_driver')
                ->cascadeOnDelete();
            $table->foreignId('tujuan_id')
                ->nullable()
                ->constrained('generate_premi_driver_tujuan')
                ->nullOnDelete();
            $table->string('kode_tujuan', 30)->nullable();
            $table->string('nama_tujuan');
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();

            $table->index(['generate_premi_driver_id', 'tujuan_id'], 'gpd_detail_generate_tujuan_idx');
            $table->index('kode_tujuan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_premi_driver_detail');
        Schema::dropIfExists('generate_premi_driver');
        Schema::dropIfExists('generate_premi_driver_tujuan');
        Schema::dropIfExists('generate_premi_driver_configs');
    }
};
