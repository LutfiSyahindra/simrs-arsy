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
        Schema::create('master_tunjangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100); // Jabatan, Anak, Istri, dll
            $table->string('kode', 50)->unique(); // optional (TJ_JABATAN)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_tunjangan');
    }
};
