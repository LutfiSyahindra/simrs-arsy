<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaji_tahap2', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('nik', 50);
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('status', 20)->nullable();
            $table->unsignedBigInteger('gaji_pokok')->default(0);
            $table->unsignedBigInteger('gaji_dibayar')->default(0);
            $table->unsignedBigInteger('total_premi')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedInteger('jumlah_sumber_premi')->default(0);
            $table->json('premi_breakdown')->nullable();
            $table->timestamps();

            $table->unique(['periode', 'nik'], 'gaji_tahap2_period_nik_unique');
            $table->index('periode');
            $table->index('nik');
            $table->index('status');
        });

        Schema::create('gaji_tahap2_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gaji_tahap2_id')
                ->constrained('gaji_tahap2')
                ->cascadeOnDelete();
            $table->string('source_key', 80);
            $table->string('source_label');
            $table->string('source_table', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('role_label')->nullable();
            $table->unsignedBigInteger('nominal')->default(0);
            $table->timestamps();

            $table->index(['gaji_tahap2_id', 'source_key']);
            $table->index(['source_table', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaji_tahap2_detail');
        Schema::dropIfExists('gaji_tahap2');
    }
};
