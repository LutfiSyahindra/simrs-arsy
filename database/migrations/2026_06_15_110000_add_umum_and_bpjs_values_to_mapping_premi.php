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
            $table->unsignedBigInteger('nilai_umum')->default(0)->after('jenis');
            $table->unsignedBigInteger('nilai_bpjs')->default(0)->after('nilai_umum');
        });

        DB::table('mapping_premi')->update([
            'nilai_umum' => DB::raw('nilai'),
            'nilai_bpjs' => DB::raw('nilai'),
        ]);
    }

    public function down(): void
    {
        Schema::table('mapping_premi', function (Blueprint $table) {
            $table->dropColumn(['nilai_umum', 'nilai_bpjs']);
        });
    }
};
