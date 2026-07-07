<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_operasi', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_pasien')
                ->nullable()
                ->after('jenis_operasi');
            $table->unsignedBigInteger('nominal_pengali')
                ->nullable()
                ->after('jumlah_pasien');
        });

        DB::table('generate_operasi_configs')
            ->where('jenis_operasi', 'bpjs')
            ->update([
                'instrumen_percent' => 80,
                'instrumen_premi_bersama_percent' => 20,
                'instrumen_petugas_percent' => 100,
                'instrumen_petugas_kelompok_20_percent' => 20,
                'instrumen_petugas_kelompok_80_percent' => 80,
                'dokter_anastesi_percent' => 100,
                'perawat_anastesi_percent' => 100,
                'perawat_anastesi_petugas_percent' => 80,
                'perawat_anastesi_premi_bersama_percent' => 20,
            ]);
    }

    public function down(): void
    {
        Schema::table('generate_operasi', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_pasien',
                'nominal_pengali',
            ]);
        });
    }
};
