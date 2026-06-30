<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premi_pelayanan_non_medis_distribution', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('premi_pelayanan_non_medis_id');
            $table->foreignId('jnsPremi_id')
                ->constrained('master_jenis_premi')
                ->restrictOnDelete();
            $table->string('nik', 50)->index();
            $table->string('pegawai_name', 150);
            $table->string('pegawai_position', 150)->nullable();
            $table->decimal('total_final', 20, 2)->default(0);
            $table->unsignedInteger('jumlah_penerima')->default(0);
            $table->decimal('total_diterima', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('premi_pelayanan_non_medis_id', 'ppnm_distribution_header_fk')
                ->references('id')
                ->on('premi_pelayanan_non_medis')
                ->cascadeOnDelete();

            $table->unique(
                ['premi_pelayanan_non_medis_id', 'nik'],
                'ppnm_distribution_header_nik_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premi_pelayanan_non_medis_distribution');
    }
};
