<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mapping_tindakan')) {
            return;
        }

        Schema::table('mapping_tindakan', function (Blueprint $table) {
            if (!Schema::hasColumn('mapping_tindakan', 'sumber_tindakan')) {
                $table->string('sumber_tindakan', 30)->default('LEGACY')->after('jnsTindakan_id');
            }

            if (!Schema::hasColumn('mapping_tindakan', 'kd_pj')) {
                $table->string('kd_pj', 10)->nullable()->after('nm_tindakan');
            }

            if (!Schema::hasColumn('mapping_tindakan', 'nm_pj')) {
                $table->string('nm_pj', 150)->nullable()->after('kd_pj');
            }

            if (!Schema::hasColumn('mapping_tindakan', 'parent_kd_tindakan')) {
                $table->string('parent_kd_tindakan', 80)->nullable()->after('nm_pj');
            }

            if (!Schema::hasColumn('mapping_tindakan', 'parent_nm_tindakan')) {
                $table->string('parent_nm_tindakan', 255)->nullable()->after('parent_kd_tindakan');
            }
        });

        $this->dropIndexIfExists('mapping_tindakan', 'mapping_tindakan_kd_tindakan_jnstindakan_id_unique');
        $this->addIndexIfMissing('mapping_tindakan', 'mapping_tindakan_sumber_tindakan_index', ['sumber_tindakan']);
        $this->addUniqueIfMissing('mapping_tindakan', 'mapping_tindakan_unique', ['jnsTindakan_id', 'sumber_tindakan', 'kd_tindakan']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('mapping_tindakan')) {
            return;
        }

        $this->dropIndexIfExists('mapping_tindakan', 'mapping_tindakan_unique');
        $this->dropIndexIfExists('mapping_tindakan', 'mapping_tindakan_sumber_tindakan_index');

        Schema::table('mapping_tindakan', function (Blueprint $table) {
            $columns = [
                'sumber_tindakan',
                'kd_pj',
                'nm_pj',
                'parent_kd_tindakan',
                'parent_nm_tindakan',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('mapping_tindakan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
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
