<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premi_pelayanan_non_medis_config', function (Blueprint $table) {
            if (! Schema::hasColumn('premi_pelayanan_non_medis_config', 'distribution_mode')) {
                $table->string('distribution_mode', 30)
                    ->default('split_evenly')
                    ->after('jnsPremi_id');
            }
        });

        Schema::table('premi_pelayanan_non_medis_distribution', function (Blueprint $table) {
            if (! Schema::hasColumn('premi_pelayanan_non_medis_distribution', 'distribution_mode')) {
                $table->string('distribution_mode', 30)
                    ->default('split_evenly')
                    ->after('pegawai_position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('premi_pelayanan_non_medis_distribution', function (Blueprint $table) {
            if (Schema::hasColumn('premi_pelayanan_non_medis_distribution', 'distribution_mode')) {
                $table->dropColumn('distribution_mode');
            }
        });

        Schema::table('premi_pelayanan_non_medis_config', function (Blueprint $table) {
            if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'distribution_mode')) {
                $table->dropColumn('distribution_mode');
            }
        });
    }
};
