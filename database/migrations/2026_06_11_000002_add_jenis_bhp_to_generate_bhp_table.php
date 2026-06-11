<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->string('jenis_bhp', 10)
                ->default('umum')
                ->after('id');
            $table->dropUnique('generate_bhp_periode_unique');
            $table->unique(
                ['periode', 'jenis_bhp'],
                'generate_bhp_periode_jenis_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->dropUnique('generate_bhp_periode_jenis_unique');
            $table->unique('periode');
            $table->dropColumn('jenis_bhp');
        });
    }
};
