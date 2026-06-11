<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_bhp_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_bhp_id')
                ->constrained('generate_bhp')
                ->cascadeOnDelete();
            $table->string('no_rawat', 17);
            $table->date('tgl_registrasi');
            $table->string('kd_pj', 3);
            $table->timestamps();

            $table->unique(
                ['generate_bhp_id', 'no_rawat'],
                'generate_bhp_detail_rawat_unique'
            );
            $table->index(['generate_bhp_id', 'tgl_registrasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_bhp_detail');
    }
};
