<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generate_vk_configs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_vk', 10)->unique();
            $table->decimal('bpjs_percent', 8, 4)->default(4);
            $table->unsignedInteger('bpjs_pembagi')->default(4);
            $table->decimal('premi_bersama_percent', 8, 4)->default(20);
            $table->timestamps();
        });

        Schema::create('generate_vk_config_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('generate_vk_configs')
                ->cascadeOnDelete();
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'pegawai_id'], 'generate_vk_config_pegawai_unique');
            $table->index('pegawai_id');
        });

        Schema::table('generate_vk', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_vk', 'total_vk_awal')) {
                $table->unsignedBigInteger('total_vk_awal')->default(0)->after('total_vk');
            }

            if (! Schema::hasColumn('generate_vk', 'bpjs_pool')) {
                $table->unsignedBigInteger('bpjs_pool')->default(0)->after('total_vk_awal');
            }

            if (! Schema::hasColumn('generate_vk', 'total_dibagikan')) {
                $table->unsignedBigInteger('total_dibagikan')->default(0)->after('bpjs_pool');
            }

            if (! Schema::hasColumn('generate_vk', 'total_premi_bersama')) {
                $table->unsignedBigInteger('total_premi_bersama')->default(0)->after('total_dibagikan');
            }

            if (! Schema::hasColumn('generate_vk', 'config_snapshot')) {
                $table->json('config_snapshot')->nullable()->after('total_premi_bersama');
            }
        });

        Schema::create('generate_vk_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generate_vk_id')
                ->constrained('generate_vk')
                ->cascadeOnDelete();
            $table->string('role', 30)->default('petugas_vk');
            $table->string('role_label')->default('Petugas VK');
            $table->string('pegawai_id', 30);
            $table->string('pegawai_name');
            $table->string('pegawai_position')->nullable();
            $table->decimal('allocation_percent', 8, 2)->nullable();
            $table->unsignedBigInteger('pool_total')->default(0);
            $table->unsignedBigInteger('total_received')->default(0);
            $table->timestamps();

            $table->index(['generate_vk_id', 'role']);
            $table->index('pegawai_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_vk_detail');

        Schema::table('generate_vk', function (Blueprint $table) {
            if (Schema::hasColumn('generate_vk', 'config_snapshot')) {
                $table->dropColumn('config_snapshot');
            }

            if (Schema::hasColumn('generate_vk', 'total_dibagikan')) {
                $table->dropColumn('total_dibagikan');
            }

            if (Schema::hasColumn('generate_vk', 'total_premi_bersama')) {
                $table->dropColumn('total_premi_bersama');
            }

            if (Schema::hasColumn('generate_vk', 'bpjs_pool')) {
                $table->dropColumn('bpjs_pool');
            }

            if (Schema::hasColumn('generate_vk', 'total_vk_awal')) {
                $table->dropColumn('total_vk_awal');
            }
        });

        Schema::dropIfExists('generate_vk_config_pegawai');
        Schema::dropIfExists('generate_vk_configs');
    }
};
