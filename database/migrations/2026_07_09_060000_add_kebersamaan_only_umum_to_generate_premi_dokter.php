<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                if (! Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_only_umum')) {
                    $table->boolean('kebersamaan_only_umum')->default(false)->after('kebersamaan_divider');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('generate_premi_dokter_configs')) {
            Schema::table('generate_premi_dokter_configs', function (Blueprint $table) {
                if (Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_only_umum')) {
                    $table->dropColumn('kebersamaan_only_umum');
                }
            });
        }
    }
};
