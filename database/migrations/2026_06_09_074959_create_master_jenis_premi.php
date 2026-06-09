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
        Schema::create('master_jenis_premi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('jenis', 100);
            $table->timestamps();

            $table->unique('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_jenis_premi');
    }
};
