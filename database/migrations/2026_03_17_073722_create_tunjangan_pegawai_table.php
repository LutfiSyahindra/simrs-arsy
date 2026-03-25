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
        Schema::create('tunjangan_pegawai', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 50); // relasi pegawai
            $table->foreignId('tunjangan_id')->constrained('master_tunjangan')->cascadeOnDelete();

            $table->decimal('nominal', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // biar tidak double
            $table->unique(['nik', 'tunjangan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tunjangan_pegawai');
    }
};
