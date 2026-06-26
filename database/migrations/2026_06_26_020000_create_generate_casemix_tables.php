<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_casemix_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 30)->default('default')->unique();
            $table->decimal('excellent_min_percent', 8, 4)->default(90);
            $table->decimal('excellent_reward_percent', 8, 4)->default(1.25);
            $table->decimal('good_min_percent', 8, 4)->default(80);
            $table->decimal('good_reward_percent', 8, 4)->default(1);
            $table->decimal('low_reward_percent', 8, 4)->default(0.75);
            $table->decimal('team_pool_percent', 8, 4)->default(70);
            $table->decimal('leader_percent', 8, 4)->default(68);
            $table->decimal('kanit_percent', 8, 4)->default(12);
            $table->decimal('inputer_percent', 8, 4)->default(20);
            $table->json('question_config')->nullable();
            $table->timestamps();
        });

        Schema::create('generate_casemix_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_casemix_configs')
                ->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'role', 'pegawai_id'], 'generate_casemix_config_pegawai_unique');
            $table->index(['role', 'pegawai_id']);
        });

        Schema::create('generate_casemix', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->unsignedBigInteger('biaya_rs')->default(0);
            $table->unsignedBigInteger('tarif_bpjs')->default(0);
            $table->unsignedBigInteger('verifikasi_hasil_bpjs')->default(0);
            $table->decimal('kerugian_awal_percent', 10, 4)->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->unsignedInteger('max_score')->default(0);
            $table->decimal('score_percent', 8, 4)->default(0);
            $table->decimal('reward_percent', 8, 4)->default(0);
            $table->unsignedBigInteger('total_reward')->default(0);
            $table->decimal('team_pool_percent', 8, 4)->default(70);
            $table->unsignedBigInteger('team_pool')->default(0);
            $table->unsignedBigInteger('non_team_pool')->default(0);
            $table->unsignedBigInteger('leader_total')->default(0);
            $table->unsignedBigInteger('kanit_total')->default(0);
            $table->unsignedBigInteger('inputer_total')->default(0);
            $table->unsignedBigInteger('total_dibagikan')->default(0);
            $table->json('config_snapshot')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generate_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

        });

        Schema::create('generate_casemix_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_casemix_id')
                ->constrained('generate_casemix')
                ->cascadeOnDelete();
            $table->string('row_type', 30);
            $table->string('question_key', 80)->nullable();
            $table->string('question_label')->nullable();
            $table->string('answer_key', 80)->nullable();
            $table->string('answer_label')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('max_score')->nullable();
            $table->string('role', 40)->nullable();
            $table->string('role_label')->nullable();
            $table->string('pegawai_id', 30)->nullable();
            $table->string('pegawai_name')->nullable();
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 4)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('amount_per_recipient')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_casemix_id', 'row_type']);
            $table->index(['role', 'pegawai_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_casemix_detail');
        Schema::dropIfExists('generate_casemix');
        Schema::dropIfExists('generate_casemix_config_pegawai');
        Schema::dropIfExists('generate_casemix_configs');
    }
};
