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
        Schema::create('generate_kamar_inap', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('jumlah_kamar');
            $table->unsignedInteger('jumlah_lama_inap');
            $table->unsignedBigInteger('nominal_hitung');
            $table->unsignedBigInteger('total_lama_inap');
            $table->string('periode', 7)->unique();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('generate_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generate_kamar_inap');
    }
};
