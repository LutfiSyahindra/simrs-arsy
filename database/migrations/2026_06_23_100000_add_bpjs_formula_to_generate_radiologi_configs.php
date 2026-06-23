<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_radiologi_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_radiologi_configs', 'bpjs_petugas_formula_rate')) {
                $table->decimal('bpjs_petugas_formula_rate', 8, 4)
                    ->default(0.04)
                    ->after('petugas_nominal');
            }

            if (! Schema::hasColumn('generate_radiologi_configs', 'bpjs_petugas_formula_divider')) {
                $table->unsignedInteger('bpjs_petugas_formula_divider')
                    ->default(4)
                    ->after('bpjs_petugas_formula_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generate_radiologi_configs', function (Blueprint $table) {
            if (Schema::hasColumn('generate_radiologi_configs', 'bpjs_petugas_formula_divider')) {
                $table->dropColumn('bpjs_petugas_formula_divider');
            }

            if (Schema::hasColumn('generate_radiologi_configs', 'bpjs_petugas_formula_rate')) {
                $table->dropColumn('bpjs_petugas_formula_rate');
            }
        });
    }
};
