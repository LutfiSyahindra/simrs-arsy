<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_premi_driver')) {
            return;
        }

        $this->setPegawaiPercentDefault(100);

        DB::table('generate_premi_driver')->update([
            'premi_pegawai_percent' => 100,
            'total_premi_pegawai' => DB::raw('grand_total'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_premi_driver')) {
            return;
        }

        $this->setPegawaiPercentDefault(80);
    }

    private function setPegawaiPercentDefault(int $default): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE generate_premi_driver MODIFY premi_pegawai_percent DECIMAL(8,4) NOT NULL DEFAULT {$default}"
            );

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(
                "ALTER TABLE generate_premi_driver ALTER COLUMN premi_pegawai_percent SET DEFAULT {$default}"
            );
        }
    }
};
