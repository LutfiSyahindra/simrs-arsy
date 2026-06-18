<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_vk', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->string('jenis_vk', 10);
            $table->string('source_key', 160);
            $table->string('sumber_tindakan', 30);
            $table->string('kd_tindakan', 80);
            $table->string('nm_tindakan');
            $table->string('kd_pj', 20)->nullable();
            $table->string('nm_pj')->nullable();
            $table->string('parent_kd_tindakan', 80)->nullable();
            $table->string('parent_nm_tindakan')->nullable();
            $table->foreignId('plotingPremi_id')
                ->nullable()
                ->constrained('master_ploting_premi')
                ->nullOnDelete();
            $table->string('kode_ploting')->nullable();
            $table->string('nama_ploting')->nullable();
            $table->unsignedInteger('jumlah_tindakan');
            $table->unsignedBigInteger('nominal_hitung');
            $table->unsignedBigInteger('total_vk');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['periode', 'jenis_vk', 'sumber_tindakan', 'kd_tindakan', 'plotingPremi_id'],
                'generate_vk_period_type_action_ploting_unique'
            );
            $table->index(['periode', 'jenis_vk']);
            $table->index(['sumber_tindakan', 'kd_tindakan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_vk');
    }
};
