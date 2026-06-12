<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_kamar_inap_detail', function (Blueprint $table) {
            $table->dropUnique('generate_kamar_detail_rawat_unique');
            $table->time('jam_masuk')->nullable()->after('tgl_masuk');
            $table->string('nama_penjamin', 100)->nullable()->after('kd_pj');
            $table->unsignedInteger('lama')->default(0)->after('kd_kamar');

            $table->unique(
                ['generate_kamar_id', 'no_rawat', 'tgl_masuk', 'jam_masuk'],
                'generate_kamar_detail_episode_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('generate_kamar_inap_detail', function (Blueprint $table) {
            $table->dropUnique('generate_kamar_detail_episode_unique');
            $table->dropColumn(['jam_masuk', 'nama_penjamin', 'lama']);
            $table->unique(
                ['generate_kamar_id', 'no_rawat'],
                'generate_kamar_detail_rawat_unique'
            );
        });
    }
};
