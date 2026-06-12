<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('generate_kamar_inap', function (Blueprint $table) {
            $table->string('jenis_kamar', 10)
                ->default('umum')
                ->after('id');
            $table->dropUnique('generate_kamar_inap_periode_unique');
            $table->unique(
                ['periode', 'jenis_kamar'],
                'generate_kamar_inap_periode_jenis_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generate_kamar_inap', function (Blueprint $table) {
            $table->dropUnique('generate_kamar_inap_periode_jenis_unique');
            $table->unique('periode');
            $table->dropColumn('jenis_kamar');
        });
    }
};
