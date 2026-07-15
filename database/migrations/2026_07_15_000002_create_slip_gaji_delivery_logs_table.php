<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('slip_gaji_delivery_logs')) {
            return;
        }

        Schema::create('slip_gaji_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7);
            $table->unsignedTinyInteger('tahap')->default(1);
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->unsignedBigInteger('gaji_id')->nullable();
            $table->string('nik', 50)->nullable();
            $table->string('nama')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('contact')->nullable();
            $table->text('message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['periode', 'tahap'], 'slip_delivery_period_stage_index');
            $table->index(['periode', 'channel', 'status'], 'slip_delivery_period_channel_status_index');
            $table->index('processed_at', 'slip_delivery_processed_at_index');
            $table->index('gaji_id', 'slip_delivery_gaji_id_index');
            $table->index('nik', 'slip_delivery_nik_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slip_gaji_delivery_logs');
    }
};
