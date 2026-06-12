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
        Schema::create('generate_kamar_inap_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_kamar_id')
                ->constrained('generate_kamar_inap')
                ->cascadeOnDelete();
            $table->string('no_rawat', 17);
            $table->date('tgl_masuk');
            $table->string('kd_pj', 3);
            $table->string('kd_kamar', 100);
            $table->timestamps();

            $table->unique(
                ['generate_kamar_id', 'no_rawat'],
                'generate_kamar_detail_rawat_unique'
            );
            $table->index(['generate_kamar_id', 'tgl_masuk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generate_kamar_inap_detail');
    }
};
