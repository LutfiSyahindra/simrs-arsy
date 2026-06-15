<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premi_pelayanan_non_medis', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_pelayanan', 10);
            $table->foreignId('generate_bhp_id')
                ->constrained('generate_bhp')
                ->restrictOnDelete();
            $table->foreignId('generate_kamar_inap_id')
                ->constrained('generate_kamar_inap')
                ->restrictOnDelete();
            $table->unsignedInteger('jumlah_transaksi')->default(0);
            $table->unsignedInteger('jumlah_jenis_tindakan')->default(0);
            $table->unsignedInteger('jumlah_mapping_premi')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('total_mapping_premi', 20, 2)->default(0);
            $table->decimal('total_bhp', 20, 2)->default(0);
            $table->decimal('total_kamar_inap', 20, 2)->default(0);
            $table->decimal('total_final', 20, 2)->default(0);
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
                ['periode', 'jenis_pelayanan'],
                'premi_non_medis_periode_jenis_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premi_pelayanan_non_medis');
    }
};
