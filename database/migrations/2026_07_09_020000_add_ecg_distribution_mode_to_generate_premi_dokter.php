<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')
            && ! Schema::hasColumn('generate_premi_dokter_configs', 'ecg_distribution_mode')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                $table->string('ecg_distribution_mode', 30)
                    ->default('split_evenly')
                    ->after('ecg_divider');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')
            && Schema::hasColumn('generate_premi_dokter_configs', 'ecg_distribution_mode')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                $table->dropColumn('ecg_distribution_mode');
            });
        }
    }
};
