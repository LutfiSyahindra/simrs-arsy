<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_bhp', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('jumlah_bhp');
            $table->unsignedBigInteger('nominal_hitung');
            $table->unsignedBigInteger('total_bhp');
            $table->string('periode', 7)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_bhp');
    }
};
