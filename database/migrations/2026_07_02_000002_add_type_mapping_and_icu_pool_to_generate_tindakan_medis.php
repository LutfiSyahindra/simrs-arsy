<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_tindakan_medis_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_tindakan_medis_configs', 'jnsPremi_umum_id')) {
                $table->foreignId('jnsPremi_umum_id')
                    ->nullable()
                    ->after('jnsPremi_id')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('generate_tindakan_medis_configs', 'jnsPremi_bpjs_id')) {
                $table->foreignId('jnsPremi_bpjs_id')
                    ->nullable()
                    ->after('jnsPremi_umum_id')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('generate_tindakan_medis_configs', 'include_bpjs_icu_pool')) {
                $table->boolean('include_bpjs_icu_pool')
                    ->default(true)
                    ->after('bpjs_ignore_vk');
            }
        });

        DB::table('generate_tindakan_medis_configs')
            ->whereNull('jnsPremi_umum_id')
            ->update(['jnsPremi_umum_id' => DB::raw('jnsPremi_id')]);

        DB::table('generate_tindakan_medis_configs')
            ->whereNull('jnsPremi_bpjs_id')
            ->update(['jnsPremi_bpjs_id' => DB::raw('jnsPremi_id')]);

        Schema::table('generate_tindakan_medis', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_tindakan_medis', 'total_icu_pool_bpjs')) {
                $table->decimal('total_icu_pool_bpjs', 20, 2)
                    ->default(0)
                    ->after('total_vk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generate_tindakan_medis', function (Blueprint $table) {
            if (Schema::hasColumn('generate_tindakan_medis', 'total_icu_pool_bpjs')) {
                $table->dropColumn('total_icu_pool_bpjs');
            }
        });

        Schema::table('generate_tindakan_medis_configs', function (Blueprint $table) {
            if (Schema::hasColumn('generate_tindakan_medis_configs', 'include_bpjs_icu_pool')) {
                $table->dropColumn('include_bpjs_icu_pool');
            }

            if (Schema::hasColumn('generate_tindakan_medis_configs', 'jnsPremi_bpjs_id')) {
                $table->dropForeign(['jnsPremi_bpjs_id']);
                $table->dropColumn('jnsPremi_bpjs_id');
            }

            if (Schema::hasColumn('generate_tindakan_medis_configs', 'jnsPremi_umum_id')) {
                $table->dropForeign(['jnsPremi_umum_id']);
                $table->dropColumn('jnsPremi_umum_id');
            }
        });
    }
};
