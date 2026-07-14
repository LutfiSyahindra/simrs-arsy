<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertStandaloneConfigTable('generate_ugd_configs', 'jenis_ugd');
        $this->convertStandaloneConfigTable('generate_bhp_configs', 'jenis_bhp');
        $this->convertStandaloneConfigTable('generate_kamar_configs', 'jenis_kamar');
        $this->convertVkNominalConfigs();
    }

    public function down(): void
    {
        $this->revertStandaloneConfigTable('generate_kamar_configs', 'jenis_kamar');
        $this->revertStandaloneConfigTable('generate_bhp_configs', 'jenis_bhp');
        $this->revertStandaloneConfigTable('generate_ugd_configs', 'jenis_ugd');
        $this->revertVkNominalConfigs();
    }

    private function convertStandaloneConfigTable(string $table, string $typeColumn): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'plotingPremi_id')) {
            return;
        }

        $existingDefaults = DB::table($table)
            ->select($typeColumn, 'default_nominal')
            ->pluck('default_nominal', $typeColumn);

        Schema::drop($table);
        $this->createStandaloneConfigTable($table, $typeColumn);
        $this->seedPlotingDefaults($table, $typeColumn, $existingDefaults->all());
    }

    private function revertStandaloneConfigTable(string $table, string $typeColumn): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'plotingPremi_id')) {
            return;
        }

        $defaults = DB::table($table)
            ->select($typeColumn, DB::raw('max(default_nominal) as default_nominal'))
            ->groupBy($typeColumn)
            ->pluck('default_nominal', $typeColumn);

        Schema::drop($table);
        Schema::create($table, function (Blueprint $tableBlueprint) use ($typeColumn) {
            $tableBlueprint->id();
            $tableBlueprint->string($typeColumn, 10)->unique();
            $tableBlueprint->unsignedBigInteger('default_nominal')->default(0);
            $tableBlueprint->timestamps();
        });

        $now = now();
        foreach (['umum', 'bpjs'] as $jenis) {
            DB::table($table)->insert([
                $typeColumn => $jenis,
                'default_nominal' => (int) ($defaults[$jenis] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function convertVkNominalConfigs(): void
    {
        if (! Schema::hasTable('generate_vk_nominal_configs')) {
            Schema::create('generate_vk_nominal_configs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_vk', 10);
                $table->foreignId('plotingPremi_id')
                    ->constrained('master_ploting_premi')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('default_nominal')->default(0);
                $table->timestamps();

                $table->unique(['jenis_vk', 'plotingPremi_id'], 'generate_vk_nominal_configs_type_ploting_unique');
            });
        }

        if (Schema::hasColumn('generate_vk_configs', 'default_nominal')) {
            $existingDefaults = DB::table('generate_vk_configs')
                ->select('jenis_vk', 'default_nominal')
                ->pluck('default_nominal', 'jenis_vk');

            $this->seedPlotingDefaults('generate_vk_nominal_configs', 'jenis_vk', $existingDefaults->all());

            Schema::table('generate_vk_configs', function (Blueprint $table) {
                if (Schema::hasColumn('generate_vk_configs', 'default_nominal')) {
                    $table->dropColumn('default_nominal');
                }
            });
        }
    }

    private function revertVkNominalConfigs(): void
    {
        $defaults = collect();

        if (Schema::hasTable('generate_vk_nominal_configs')) {
            $defaults = DB::table('generate_vk_nominal_configs')
                ->select('jenis_vk', DB::raw('max(default_nominal) as default_nominal'))
                ->groupBy('jenis_vk')
                ->pluck('default_nominal', 'jenis_vk');

            Schema::dropIfExists('generate_vk_nominal_configs');
        }

        if (Schema::hasTable('generate_vk_configs') && ! Schema::hasColumn('generate_vk_configs', 'default_nominal')) {
            Schema::table('generate_vk_configs', function (Blueprint $table) {
                $table->unsignedBigInteger('default_nominal')->default(0)->after('jenis_vk');
            });
        }

        if (Schema::hasTable('generate_vk_configs') && Schema::hasColumn('generate_vk_configs', 'default_nominal')) {
            foreach (['umum', 'bpjs'] as $jenis) {
                DB::table('generate_vk_configs')
                    ->where('jenis_vk', $jenis)
                    ->update(['default_nominal' => (int) ($defaults[$jenis] ?? 0)]);
            }
        }
    }

    private function createStandaloneConfigTable(string $table, string $typeColumn): void
    {
        Schema::create($table, function (Blueprint $tableBlueprint) use ($table, $typeColumn) {
            $tableBlueprint->id();
            $tableBlueprint->string($typeColumn, 10);
            $tableBlueprint->foreignId('plotingPremi_id')
                ->constrained('master_ploting_premi')
                ->cascadeOnDelete();
            $tableBlueprint->unsignedBigInteger('default_nominal')->default(0);
            $tableBlueprint->timestamps();

            $tableBlueprint->unique(
                [$typeColumn, 'plotingPremi_id'],
                "{$table}_type_ploting_unique"
            );
        });
    }

    private function seedPlotingDefaults(string $table, string $typeColumn, array $defaultsByType): void
    {
        $plotingIds = DB::table('master_ploting_premi')->pluck('id');

        if ($plotingIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];
        foreach (['umum', 'bpjs'] as $jenis) {
            foreach ($plotingIds as $plotingId) {
                $rows[] = [
                    $typeColumn => $jenis,
                    'plotingPremi_id' => $plotingId,
                    'default_nominal' => (int) ($defaultsByType[$jenis] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table($table)->insertOrIgnore($rows);
    }
};
