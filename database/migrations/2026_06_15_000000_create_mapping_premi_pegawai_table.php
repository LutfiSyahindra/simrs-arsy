<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_premi_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jnsPremi_id')
                ->constrained('master_jenis_premi')
                ->cascadeOnDelete();
            $table->string('nik', 50)->index();
            $table->timestamps();

            $table->unique(
                ['jnsPremi_id', 'nik'],
                'mapping_premi_pegawai_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_premi_pegawai');
    }
};
