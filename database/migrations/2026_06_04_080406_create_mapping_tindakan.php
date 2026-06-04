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
        Schema::create('mapping_tindakan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jnsTindakan_id')->constrained('master_jenis_tindakan')->cascadeOnDelete();
            $table->string('sumber_tindakan', 30)->index();
            $table->string('kd_tindakan', 80)->index();
            $table->string('nm_tindakan', 255);
            $table->string('kd_pj', 10)->nullable();
            $table->string('nm_pj', 150)->nullable();
            $table->string('parent_kd_tindakan', 80)->nullable();
            $table->string('parent_nm_tindakan', 255)->nullable();

            $table->timestamps();

            $table->unique(
                ['jnsTindakan_id', 'sumber_tindakan', 'kd_tindakan'],
                'mapping_tindakan_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_tindakan');
    }
};
