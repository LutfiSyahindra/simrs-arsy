<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mapping_premi', function (Blueprint $table) {
            if (! Schema::hasColumn('mapping_premi', 'jenis_umum')) {
                $table->enum('jenis_umum', ['persen', 'nominal'])->default('persen')->after('jenis');
            }

            if (! Schema::hasColumn('mapping_premi', 'jenis_bpjs')) {
                $table->enum('jenis_bpjs', ['persen', 'nominal'])->default('persen')->after('jenis_umum');
            }

            if (! Schema::hasColumn('mapping_premi', 'nilai_bersama_umum')) {
                $table->unsignedBigInteger('nilai_bersama_umum')->default(0)->after('nilai_bpjs');
            }

            if (! Schema::hasColumn('mapping_premi', 'nilai_bersama_bpjs')) {
                $table->unsignedBigInteger('nilai_bersama_bpjs')->default(0)->after('nilai_bersama_umum');
            }
        });

        DB::table('mapping_premi')->update([
            'jenis_umum' => DB::raw('jenis'),
            'jenis_bpjs' => DB::raw('jenis'),
        ]);
    }

    public function down(): void
    {
        Schema::table('mapping_premi', function (Blueprint $table) {
            $columns = collect([
                'jenis_umum',
                'jenis_bpjs',
                'nilai_bersama_umum',
                'nilai_bersama_bpjs',
            ])->filter(fn ($column) => Schema::hasColumn('mapping_premi', $column))->all();

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
