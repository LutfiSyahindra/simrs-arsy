<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaji_tahap1_dokter_config', function (Blueprint $table) {
            $table->id();
            $table->string('kd_dokter', 30);
            $table->string('nm_dokter');
            $table->string('kd_sps', 20)->nullable();
            $table->string('nm_sps')->nullable();
            $table->boolean('include_salary')->default(false);
            $table->json('premium_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('kd_dokter', 'gaji_tahap1_dokter_config_code_unique');
            $table->index('is_active', 'gaji_tahap1_dokter_config_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaji_tahap1_dokter_config');
    }
};
