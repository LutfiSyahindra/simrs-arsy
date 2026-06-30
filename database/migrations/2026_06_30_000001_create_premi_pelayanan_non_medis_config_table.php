<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premi_pelayanan_non_medis_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jnsPremi_id')
                ->nullable()
                ->constrained('master_jenis_premi')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premi_pelayanan_non_medis_config');
    }
};
