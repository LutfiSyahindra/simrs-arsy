<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premi_pelayanan_non_medis_config', function (Blueprint $table) {
            if (! Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_umum_id')) {
                $table->foreignId('jnsPremi_umum_id')
                    ->nullable()
                    ->after('jnsPremi_id')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_bpjs_id')) {
                $table->foreignId('jnsPremi_bpjs_id')
                    ->nullable()
                    ->after('jnsPremi_umum_id')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }
        });

        DB::table('premi_pelayanan_non_medis_config')
            ->whereNull('jnsPremi_umum_id')
            ->update(['jnsPremi_umum_id' => DB::raw('jnsPremi_id')]);

        DB::table('premi_pelayanan_non_medis_config')
            ->whereNull('jnsPremi_bpjs_id')
            ->update(['jnsPremi_bpjs_id' => DB::raw('jnsPremi_id')]);
    }

    public function down(): void
    {
        Schema::table('premi_pelayanan_non_medis_config', function (Blueprint $table) {
            if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_bpjs_id')) {
                $table->dropForeign(['jnsPremi_bpjs_id']);
                $table->dropColumn('jnsPremi_bpjs_id');
            }

            if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_umum_id')) {
                $table->dropForeign(['jnsPremi_umum_id']);
                $table->dropColumn('jnsPremi_umum_id');
            }
        });
    }
};
