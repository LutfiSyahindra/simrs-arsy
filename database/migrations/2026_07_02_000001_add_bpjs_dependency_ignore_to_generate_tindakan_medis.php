<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_tindakan_medis_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_ugd')) {
                $table->boolean('bpjs_ignore_ugd')->default(false)->after('ignore_nicu');
            }

            if (! Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_vk')) {
                $table->boolean('bpjs_ignore_vk')->default(false)->after('bpjs_ignore_ugd');
            }
        });

        Schema::table('generate_tindakan_medis', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_tindakan_medis', 'bpjs_ignore_ugd')) {
                $table->boolean('bpjs_ignore_ugd')->default(false)->after('ignore_nicu');
            }

            if (! Schema::hasColumn('generate_tindakan_medis', 'bpjs_ignore_vk')) {
                $table->boolean('bpjs_ignore_vk')->default(false)->after('bpjs_ignore_ugd');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generate_tindakan_medis', function (Blueprint $table) {
            if (Schema::hasColumn('generate_tindakan_medis', 'bpjs_ignore_vk')) {
                $table->dropColumn('bpjs_ignore_vk');
            }

            if (Schema::hasColumn('generate_tindakan_medis', 'bpjs_ignore_ugd')) {
                $table->dropColumn('bpjs_ignore_ugd');
            }
        });

        Schema::table('generate_tindakan_medis_configs', function (Blueprint $table) {
            if (Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_vk')) {
                $table->dropColumn('bpjs_ignore_vk');
            }

            if (Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_ugd')) {
                $table->dropColumn('bpjs_ignore_ugd');
            }
        });
    }
};
