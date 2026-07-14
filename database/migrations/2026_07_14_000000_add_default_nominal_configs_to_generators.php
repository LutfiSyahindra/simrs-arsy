<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_ugd_configs')) {
            Schema::create('generate_ugd_configs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_ugd', 10);
                $table->foreignId('plotingPremi_id')
                    ->constrained('master_ploting_premi')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('default_nominal')->default(0);
                $table->timestamps();

                $table->unique(['jenis_ugd', 'plotingPremi_id'], 'generate_ugd_configs_type_ploting_unique');
            });
        }

        if (! Schema::hasTable('generate_bhp_configs')) {
            Schema::create('generate_bhp_configs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_bhp', 10);
                $table->foreignId('plotingPremi_id')
                    ->constrained('master_ploting_premi')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('default_nominal')->default(0);
                $table->timestamps();

                $table->unique(['jenis_bhp', 'plotingPremi_id'], 'generate_bhp_configs_type_ploting_unique');
            });
        }

        if (! Schema::hasTable('generate_kamar_configs')) {
            Schema::create('generate_kamar_configs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_kamar', 10);
                $table->foreignId('plotingPremi_id')
                    ->constrained('master_ploting_premi')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('default_nominal')->default(0);
                $table->timestamps();

                $table->unique(['jenis_kamar', 'plotingPremi_id'], 'generate_kamar_configs_type_ploting_unique');
            });
        }

        if (! Schema::hasTable('generate_vk_nominal_configs')) {
            Schema::create('generate_vk_nominal_configs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_vk', 10);
                $table->foreignId('plotingPremi_id')
                    ->constrained('master_ploting_premi')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('default_nominal')->default(0);
                $table->timestamps();

                $table->unique(['jenis_vk', 'plotingPremi_id'], 'generate_vk_nominal_configs_type_ploting_unique');
            });
        }

        if (Schema::hasTable('generate_operasi_configs')) {
            Schema::table('generate_operasi_configs', function (Blueprint $table) {
                if (! Schema::hasColumn('generate_operasi_configs', 'default_nominal')) {
                    $table->unsignedBigInteger('default_nominal')->default(0)->after('jenis_operasi');
                }
            });
        }

    }

    public function down(): void
    {
        if (Schema::hasTable('generate_operasi_configs')) {
            Schema::table('generate_operasi_configs', function (Blueprint $table) {
                if (Schema::hasColumn('generate_operasi_configs', 'default_nominal')) {
                    $table->dropColumn('default_nominal');
                }
            });
        }

        Schema::dropIfExists('generate_vk_nominal_configs');
        Schema::dropIfExists('generate_kamar_configs');
        Schema::dropIfExists('generate_bhp_configs');
        Schema::dropIfExists('generate_ugd_configs');
    }
};
