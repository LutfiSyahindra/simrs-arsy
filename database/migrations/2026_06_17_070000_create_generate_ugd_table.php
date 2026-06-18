<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_ugd', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_ugd', 10);
            $table->string('kd_dokter', 20);
            $table->string('nm_dokter');
            $table->foreignId('plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('kode_ploting')->nullable();
            $table->string('nama_ploting')->nullable();
            $table->unsignedInteger('jumlah_pasien');
            $table->unsignedBigInteger('nominal_hitung');
            $table->unsignedBigInteger('total_ugd');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['periode', 'jenis_ugd', 'kd_dokter', 'plotingPremi_id'],
                'generate_ugd_period_type_doctor_ploting_unique'
            );
            $table->index(['periode', 'jenis_ugd']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_ugd');
    }
};
