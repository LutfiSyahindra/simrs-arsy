<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_icu_config_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_icu_configs')
                ->cascadeOnDelete();
            $table->foreignId('jnsTindakan_id')
                ->constrained('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['config_id', 'jnsTindakan_id'], 'generate_icu_config_tindakan_unique');
            $table->index('jnsTindakan_id');
        });

        Schema::create('generate_icu_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_icu_configs')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'role', 'pegawai_id'], 'generate_icu_config_pegawai_unique');
            $table->index(['role', 'pegawai_id']);
        });

        Schema::create('generate_icu_recipient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_icu_id')
                ->constrained('generate_icu')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('role_label');
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_icu_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_icu_recipient');
        Schema::dropIfExists('generate_icu_config_pegawai');
        Schema::dropIfExists('generate_icu_config_tindakan');
    }
};
