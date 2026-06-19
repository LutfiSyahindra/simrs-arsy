<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_operasi_configs', function (Blueprint $table) {
            $table->decimal('perawat_anastesi_petugas_percent', 8, 2)
                ->default(80)
                ->after('perawat_anastesi_percent');
            $table->decimal('perawat_anastesi_premi_bersama_percent', 8, 2)
                ->default(20)
                ->after('perawat_anastesi_petugas_percent');
        });
    }

    public function down(): void
    {
        Schema::table('generate_operasi_configs', function (Blueprint $table) {
            $table->dropColumn([
                'perawat_anastesi_petugas_percent',
                'perawat_anastesi_premi_bersama_percent',
            ]);
        });
    }
};
