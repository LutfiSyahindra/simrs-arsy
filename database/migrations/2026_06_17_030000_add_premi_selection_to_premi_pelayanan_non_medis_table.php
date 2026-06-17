<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('premi_pelayanan_non_medis')) {
            return;
        }

        Schema::table('premi_pelayanan_non_medis', function (Blueprint $table) {
            if (! Schema::hasColumn('premi_pelayanan_non_medis', 'jnsPremi_id')) {
                $table->foreignId('jnsPremi_id')
                    ->nullable()
                    ->after('jenis_pelayanan')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('premi_pelayanan_non_medis', 'kode_premi')) {
                $table->string('kode_premi', 50)->nullable()->after('jnsPremi_id');
            }

            if (! Schema::hasColumn('premi_pelayanan_non_medis', 'nama_premi')) {
                $table->string('nama_premi', 100)->nullable()->after('kode_premi');
            }
        });

        if ($this->indexExists('premi_non_medis_periode_jenis_unique')) {
            Schema::table('premi_pelayanan_non_medis', function (Blueprint $table) {
                $table->dropUnique('premi_non_medis_periode_jenis_unique');
            });
        }

        if (! $this->indexExists('premi_non_medis_periode_jenis_premi_unique')) {
            Schema::table('premi_pelayanan_non_medis', function (Blueprint $table) {
                $table->unique(
                    ['periode', 'jenis_pelayanan', 'jnsPremi_id'],
                    'premi_non_medis_periode_jenis_premi_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('premi_pelayanan_non_medis')) {
            return;
        }

        if ($this->indexExists('premi_non_medis_periode_jenis_premi_unique')) {
            Schema::table('premi_pelayanan_non_medis', function (Blueprint $table) {
                $table->dropUnique('premi_non_medis_periode_jenis_premi_unique');
            });
        }

        Schema::table('premi_pelayanan_non_medis', function (Blueprint $table) {
            if (Schema::hasColumn('premi_pelayanan_non_medis', 'jnsPremi_id')) {
                $table->dropConstrainedForeignId('jnsPremi_id');
            }

            foreach (['kode_premi', 'nama_premi'] as $column) {
                if (Schema::hasColumn('premi_pelayanan_non_medis', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (! $this->indexExists('premi_non_medis_periode_jenis_unique')) {
            Schema::table('premi_pelayanan_non_medis', function (Blueprint $table) {
                $table->unique(
                    ['periode', 'jenis_pelayanan'],
                    'premi_non_medis_periode_jenis_unique'
                );
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        return ! empty(DB::select(
            'SHOW INDEX FROM premi_pelayanan_non_medis WHERE Key_name = ?',
            [$indexName]
        ));
    }
};
