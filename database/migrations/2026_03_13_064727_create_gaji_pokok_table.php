<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gaji_pokok', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->index();
            $table->string('nama');
            $table->string('jbtn'); // jabatan
            $table->string('stts_kerja'); // status kerja (tetap, kontrak, dll)
            $table->integer('masa_kerja'); // dalam tahun
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaji_pokok');
    }
};
