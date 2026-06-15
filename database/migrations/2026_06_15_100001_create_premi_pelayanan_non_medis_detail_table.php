<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premi_pelayanan_non_medis_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('premi_pelayanan_non_medis_id');
            $table->unsignedBigInteger('mapping_premi_id')->nullable();
            $table->unsignedBigInteger('jnsPremi_id');
            $table->unsignedBigInteger('jnsTindakan_id');
            $table->string('kode_premi', 50)->nullable();
            $table->string('nama_premi', 100);
            $table->string('kode_jenis_tindakan', 50)->nullable();
            $table->string('nama_jenis_tindakan', 100);
            $table->string('jenis_mapping', 10);
            $table->decimal('nilai_mapping', 20, 2)->default(0);
            $table->unsignedInteger('jumlah_data')->default(0);
            $table->decimal('total_biaya_rawat', 20, 2)->default(0);
            $table->decimal('dasar_hitung', 20, 2)->default(0);
            $table->decimal('hasil_mapping', 20, 2)->default(0);
            $table->json('data_tindakan');
            $table->timestamps();

            $table->foreign('premi_pelayanan_non_medis_id', 'ppnm_detail_header_fk')
                ->references('id')
                ->on('premi_pelayanan_non_medis')
                ->cascadeOnDelete();
            $table->foreign('mapping_premi_id', 'ppnm_detail_mapping_fk')
                ->references('id')
                ->on('mapping_premi')
                ->nullOnDelete();
            $table->foreign('jnsPremi_id', 'ppnm_detail_premi_fk')
                ->references('id')
                ->on('master_jenis_premi')
                ->restrictOnDelete();
            $table->foreign('jnsTindakan_id', 'ppnm_detail_tindakan_fk')
                ->references('id')
                ->on('master_jenis_tindakan')
                ->restrictOnDelete();

            $table->unique(
                ['premi_pelayanan_non_medis_id', 'mapping_premi_id'],
                'ppnm_detail_mapping_unique'
            );
            $table->index(
                ['premi_pelayanan_non_medis_id', 'jnsTindakan_id'],
                'ppnm_detail_tindakan_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premi_pelayanan_non_medis_detail');
    }
};
