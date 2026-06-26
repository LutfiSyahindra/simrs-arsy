<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_apotek_configs') || ! Schema::hasTable('generate_apotek')) {
            return;
        }

        Schema::table('generate_apotek_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_apotek_configs', 'kategori_premi')) {
                $table->string('kategori_premi', 20)->default('apotek')->after('id');
            }

            if (! Schema::hasColumn('generate_apotek_configs', 'jnsPremi_id')) {
                $table->foreignId('jnsPremi_id')
                    ->nullable()
                    ->after('jnsTindakan_id')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }
        });

        DB::table('generate_apotek_configs')
            ->whereNull('kategori_premi')
            ->orWhere('kategori_premi', '')
            ->update(['kategori_premi' => 'apotek']);

        $this->dropIndexIfExists('generate_apotek_configs', 'generate_apotek_configs_jenis_apotek_unique');
        $this->addUniqueIfMissing(
            'generate_apotek_configs',
            'generate_apotek_configs_category_type_unique',
            ['kategori_premi', 'jenis_apotek']
        );

        Schema::table('generate_apotek', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_apotek', 'kategori_premi')) {
                $table->string('kategori_premi', 20)->default('apotek')->after('id');
            }

            if (! Schema::hasColumn('generate_apotek', 'source_period_mode')) {
                $table->string('source_period_mode', 20)->default('current')->after('source_periode');
            }

            if (! Schema::hasColumn('generate_apotek', 'jnsPremi_id')) {
                $table->foreignId('jnsPremi_id')
                    ->nullable()
                    ->after('jnsTindakan_id')
                    ->constrained('master_jenis_premi')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('generate_apotek', 'kode_premi')) {
                $table->string('kode_premi', 50)->nullable()->after('jnsPremi_id');
            }

            if (! Schema::hasColumn('generate_apotek', 'nama_premi')) {
                $table->string('nama_premi')->nullable()->after('kode_premi');
            }

            if (! Schema::hasColumn('generate_apotek', 'pembagi')) {
                $table->unsignedInteger('pembagi')->default(1)->after('nama_premi');
            }

            if (! Schema::hasColumn('generate_apotek', 'jumlah_tindakan')) {
                $table->unsignedInteger('jumlah_tindakan')->default(0)->after('jumlah_obat');
            }

            if (! Schema::hasColumn('generate_apotek', 'jumlah_mapping_premi')) {
                $table->unsignedInteger('jumlah_mapping_premi')->default(0)->after('jumlah_tindakan');
            }

            if (! Schema::hasColumn('generate_apotek', 'total_biaya_rawat')) {
                $table->unsignedBigInteger('total_biaya_rawat')->default(0)->after('grand_total');
            }

            if (! Schema::hasColumn('generate_apotek', 'total_mapping_premi')) {
                $table->unsignedBigInteger('total_mapping_premi')->default(0)->after('total_biaya_rawat');
            }

            if (! Schema::hasColumn('generate_apotek', 'total_final')) {
                $table->unsignedBigInteger('total_final')->default(0)->after('total_mapping_premi');
            }
        });

        DB::table('generate_apotek')
            ->whereNull('kategori_premi')
            ->orWhere('kategori_premi', '')
            ->update(['kategori_premi' => 'apotek']);

        DB::table('generate_apotek')
            ->whereNull('source_period_mode')
            ->orWhere('source_period_mode', '')
            ->update(['source_period_mode' => 'current']);

        $this->dropIndexIfExists('generate_apotek', 'generate_apotek_period_type_unique');
        $this->addUniqueIfMissing(
            'generate_apotek',
            'generate_apotek_category_period_type_unique',
            ['kategori_premi', 'periode', 'jenis_apotek']
        );
        $this->addIndexIfMissing(
            'generate_apotek',
            'generate_apotek_category_period_type_index',
            ['kategori_premi', 'periode', 'jenis_apotek']
        );

        Schema::table('generate_apotek_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_apotek_detail', 'mapping_premi_id')) {
                $table->unsignedBigInteger('mapping_premi_id')->nullable()->after('mapping_tindakan_id');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'kd_tindakan')) {
                $table->string('kd_tindakan', 80)->nullable()->after('status');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'nm_tindakan')) {
                $table->string('nm_tindakan')->nullable()->after('kd_tindakan');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'kd_dokter')) {
                $table->string('kd_dokter', 30)->nullable()->after('nm_tindakan');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'nm_dokter')) {
                $table->string('nm_dokter')->nullable()->after('kd_dokter');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'nip')) {
                $table->string('nip', 30)->nullable()->after('nm_dokter');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'nama_petugas')) {
                $table->string('nama_petugas')->nullable()->after('nip');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'biaya_rawat')) {
                $table->unsignedBigInteger('biaya_rawat')->default(0)->after('nama_petugas');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'jenis_mapping')) {
                $table->string('jenis_mapping', 20)->nullable()->after('biaya_rawat');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'nilai_mapping')) {
                $table->decimal('nilai_mapping', 14, 4)->default(0)->after('jenis_mapping');
            }

            if (! Schema::hasColumn('generate_apotek_detail', 'hasil_mapping')) {
                $table->unsignedBigInteger('hasil_mapping')->default(0)->after('nilai_mapping');
            }
        });

        $this->addIndexIfMissing('generate_apotek_detail', 'generate_apotek_detail_mapping_premi_index', ['mapping_premi_id']);
        $this->addIndexIfMissing('generate_apotek_detail', 'generate_apotek_detail_tindakan_index', ['sumber_tindakan', 'kd_tindakan']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_apotek_configs') || ! Schema::hasTable('generate_apotek')) {
            return;
        }

        $this->dropIndexIfExists('generate_apotek_detail', 'generate_apotek_detail_tindakan_index');
        $this->dropIndexIfExists('generate_apotek_detail', 'generate_apotek_detail_mapping_premi_index');

        Schema::table('generate_apotek_detail', function (Blueprint $table) {
            foreach ([
                'mapping_premi_id',
                'kd_tindakan',
                'nm_tindakan',
                'kd_dokter',
                'nm_dokter',
                'nip',
                'nama_petugas',
                'biaya_rawat',
                'jenis_mapping',
                'nilai_mapping',
                'hasil_mapping',
            ] as $column) {
                if (Schema::hasColumn('generate_apotek_detail', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $this->dropIndexIfExists('generate_apotek', 'generate_apotek_category_period_type_unique');
        $this->dropIndexIfExists('generate_apotek', 'generate_apotek_category_period_type_index');

        Schema::table('generate_apotek', function (Blueprint $table) {
            foreach ([
                'kategori_premi',
                'source_period_mode',
                'jnsPremi_id',
                'kode_premi',
                'nama_premi',
                'pembagi',
                'jumlah_tindakan',
                'jumlah_mapping_premi',
                'total_biaya_rawat',
                'total_mapping_premi',
                'total_final',
            ] as $column) {
                if (Schema::hasColumn('generate_apotek', $column)) {
                    if ($column === 'jnsPremi_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        $this->addUniqueIfMissing(
            'generate_apotek',
            'generate_apotek_period_type_unique',
            ['periode', 'jenis_apotek']
        );

        $this->dropIndexIfExists('generate_apotek_configs', 'generate_apotek_configs_category_type_unique');

        Schema::table('generate_apotek_configs', function (Blueprint $table) {
            if (Schema::hasColumn('generate_apotek_configs', 'jnsPremi_id')) {
                $table->dropConstrainedForeignId('jnsPremi_id');
            }

            if (Schema::hasColumn('generate_apotek_configs', 'kategori_premi')) {
                $table->dropColumn('kategori_premi');
            }
        });

        $this->addUniqueIfMissing(
            'generate_apotek_configs',
            'generate_apotek_configs_jenis_apotek_unique',
            ['jenis_apotek']
        );
    }

    protected function indexExists(string $table, string $index): bool
    {
        return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
    }

    protected function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    protected function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        $columnsSql = collect($columns)
            ->map(fn ($column) => "`{$column}`")
            ->implode(', ');

        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columnsSql})");
    }

    protected function addUniqueIfMissing(string $table, string $index, array $columns): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        $columnsSql = collect($columns)
            ->map(fn ($column) => "`{$column}`")
            ->implode(', ');

        DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$index}` ({$columnsSql})");
    }
};
