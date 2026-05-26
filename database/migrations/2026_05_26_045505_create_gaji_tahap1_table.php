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
        Schema::create('gaji_tahap1', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50);
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('gaji_pokok')->default(0);
            $table->unsignedBigInteger('gaji_dibayar')->default(0);
            $table->unsignedBigInteger('tunjangan')->default(0);
            $table->string('periode', 7)->nullable();
            $table->timestamps();

            $table->index('nik');
            $table->index('periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaji_tahap1');
    }
};
