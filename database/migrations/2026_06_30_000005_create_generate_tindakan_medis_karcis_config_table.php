<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_tindakan_medis_karcis_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                'jnsTindakan_id',
                'gtm_karcis_config_tindakan_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_tindakan_medis_karcis_config');
    }
};
