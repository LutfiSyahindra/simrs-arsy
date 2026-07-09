<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_premi_dokter_configs')) {
            return;
        }

        Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_premi_dokter_configs', 'igd_nominal_per_pasien')) {
                $table->unsignedBigInteger('igd_nominal_per_pasien')->default(30000)->after('konsul_wa_nominal');
            }

            if (! Schema::hasColumn('generate_premi_dokter_configs', 'kehadiran_nominal_per_hadir')) {
                $table->unsignedBigInteger('kehadiran_nominal_per_hadir')->default(250000)->after('igd_nominal_per_pasien');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_premi_dokter_configs')) {
            return;
        }

        Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
            if (Schema::hasColumn('generate_premi_dokter_configs', 'kehadiran_nominal_per_hadir')) {
                $table->dropColumn('kehadiran_nominal_per_hadir');
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'igd_nominal_per_pasien')) {
                $table->dropColumn('igd_nominal_per_pasien');
            }
        });
    }
};
