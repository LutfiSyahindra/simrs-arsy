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
        Schema::create('skoring_pegawai', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 50)->index();
            $table->foreignId('skor_id')->constrained('master_skor')->cascadeOnDelete();
            $table->integer('bobot_skor')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nik', 'skor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skoring_pegawai');
    }
};
