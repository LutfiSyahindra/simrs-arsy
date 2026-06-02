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
        Schema::create('master_skor', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 100);
            $table->string('keterangan', 150);
            $table->integer('bobot_skor')->default(0);
            $table->timestamps();

            $table->unique(['jenis', 'keterangan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_skor');
    }
};
