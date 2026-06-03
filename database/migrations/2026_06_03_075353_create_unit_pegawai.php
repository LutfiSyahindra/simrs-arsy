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
        Schema::create('unit_pegawai', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 50)->index();
            $table->foreignId('unit_id')->constrained('master_unit')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nik', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_pegawai');
    }
};
