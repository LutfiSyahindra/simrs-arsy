<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('potongan_pegawai')) {
            return;
        }

        Schema::create('potongan_pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->index();
            $table->foreignId('potongan_id')->constrained('master_potongan')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['nik', 'potongan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('potongan_pegawai');
    }
};
