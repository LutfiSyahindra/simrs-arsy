<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_icu_configs', function (Blueprint $table) {
            $table->unsignedInteger('perawat_icu_divider')
                ->default(4)
                ->after('pegawai_icu_khusus_percent');
        });
    }

    public function down(): void
    {
        Schema::table('generate_icu_configs', function (Blueprint $table) {
            $table->dropColumn('perawat_icu_divider');
        });
    }
};
