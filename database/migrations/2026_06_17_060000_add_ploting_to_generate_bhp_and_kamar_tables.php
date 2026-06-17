<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->unsignedBigInteger('plotingPremi_id')
                ->nullable()
                ->after('jenis_bhp');
            $table->string('kode_ploting', 50)
                ->nullable()
                ->after('plotingPremi_id');
            $table->string('nama_ploting', 100)
                ->nullable()
                ->after('kode_ploting');
        });

        Schema::table('generate_kamar_inap', function (Blueprint $table) {
            $table->unsignedBigInteger('plotingPremi_id')
                ->nullable()
                ->after('jenis_kamar');
            $table->string('kode_ploting', 50)
                ->nullable()
                ->after('plotingPremi_id');
            $table->string('nama_ploting', 100)
                ->nullable()
                ->after('kode_ploting');
        });

        $firstPloting = DB::table('master_ploting_premi')
            ->orderBy('id')
            ->first(['id', 'kode', 'ploting']);

        if ($firstPloting) {
            DB::table('generate_bhp')
                ->whereNull('plotingPremi_id')
                ->update([
                    'plotingPremi_id' => $firstPloting->id,
                    'kode_ploting' => $firstPloting->kode,
                    'nama_ploting' => $firstPloting->ploting,
                ]);

            DB::table('generate_kamar_inap')
                ->whereNull('plotingPremi_id')
                ->update([
                    'plotingPremi_id' => $firstPloting->id,
                    'kode_ploting' => $firstPloting->kode,
                    'nama_ploting' => $firstPloting->ploting,
                ]);
        }

        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->dropUnique('generate_bhp_periode_jenis_unique');
            $table->foreign('plotingPremi_id', 'generate_bhp_ploting_fk')
                ->references('id')
                ->on('master_ploting_premi')
                ->nullOnDelete();
            $table->unique(
                ['periode', 'jenis_bhp', 'plotingPremi_id'],
                'generate_bhp_periode_jenis_ploting_unique'
            );
        });

        Schema::table('generate_kamar_inap', function (Blueprint $table) {
            $table->dropUnique('generate_kamar_inap_periode_jenis_unique');
            $table->foreign('plotingPremi_id', 'generate_kamar_ploting_fk')
                ->references('id')
                ->on('master_ploting_premi')
                ->nullOnDelete();
            $table->unique(
                ['periode', 'jenis_kamar', 'plotingPremi_id'],
                'generate_kamar_periode_jenis_ploting_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->dropUnique('generate_bhp_periode_jenis_ploting_unique');
            $table->dropForeign('generate_bhp_ploting_fk');
            $table->dropColumn(['plotingPremi_id', 'kode_ploting', 'nama_ploting']);
            $table->unique(
                ['periode', 'jenis_bhp'],
                'generate_bhp_periode_jenis_unique'
            );
        });

        Schema::table('generate_kamar_inap', function (Blueprint $table) {
            $table->dropUnique('generate_kamar_periode_jenis_ploting_unique');
            $table->dropForeign('generate_kamar_ploting_fk');
            $table->dropColumn(['plotingPremi_id', 'kode_ploting', 'nama_ploting']);
            $table->unique(
                ['periode', 'jenis_kamar'],
                'generate_kamar_inap_periode_jenis_unique'
            );
        });
    }
};
