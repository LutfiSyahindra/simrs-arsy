<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mapping_premi')) {
            return;
        }

        if (Schema::hasColumn('mapping_premi', 'persentase')
            && !Schema::hasColumn('mapping_premi', 'nilai')) {
            Schema::table('mapping_premi', function (Blueprint $table) {
                $table->renameColumn('persentase', 'nilai');
            });
        }

        if (!Schema::hasColumn('mapping_premi', 'jenis')) {
            Schema::table('mapping_premi', function (Blueprint $table) {
                $table->enum('jenis', ['persen', 'nominal'])
                    ->default('persen')
                    ->after('nilai');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('mapping_premi')) {
            return;
        }

        if (Schema::hasColumn('mapping_premi', 'jenis')) {
            Schema::table('mapping_premi', function (Blueprint $table) {
                $table->dropColumn('jenis');
            });
        }

        if (Schema::hasColumn('mapping_premi', 'nilai')
            && !Schema::hasColumn('mapping_premi', 'persentase')) {
            Schema::table('mapping_premi', function (Blueprint $table) {
                $table->renameColumn('nilai', 'persentase');
            });
        }
    }
};
