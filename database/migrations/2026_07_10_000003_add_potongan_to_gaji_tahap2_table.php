<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gaji_tahap2')) {
            return;
        }

        Schema::table('gaji_tahap2', function (Blueprint $table) {
            if (! Schema::hasColumn('gaji_tahap2', 'total_potongan')) {
                $table->unsignedBigInteger('total_potongan')->default(0)->after('total_premi');
            }

            if (! Schema::hasColumn('gaji_tahap2', 'potongan_breakdown')) {
                $table->json('potongan_breakdown')->nullable()->after('premi_breakdown');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gaji_tahap2')) {
            return;
        }

        Schema::table('gaji_tahap2', function (Blueprint $table) {
            if (Schema::hasColumn('gaji_tahap2', 'potongan_breakdown')) {
                $table->dropColumn('potongan_breakdown');
            }

            if (Schema::hasColumn('gaji_tahap2', 'total_potongan')) {
                $table->dropColumn('total_potongan');
            }
        });
    }
};
