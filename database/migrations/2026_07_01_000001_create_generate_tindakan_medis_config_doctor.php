<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_tindakan_medis_config_doctor')) {
            return;
        }

        Schema::create('generate_tindakan_medis_config_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_tindakan_medis_configs')
                ->cascadeOnDelete();
            $table->string('kd_dokter', 30);
            $table->string('nm_dokter')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'kd_dokter'], 'gtm_config_doctor_unique');
            $table->index('kd_dokter', 'gtm_config_doctor_code_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_tindakan_medis_config_doctor');
    }
};
