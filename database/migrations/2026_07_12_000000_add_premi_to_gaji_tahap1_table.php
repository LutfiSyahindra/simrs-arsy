<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gaji_tahap1')) {
            return;
        }

        Schema::table('gaji_tahap1', function (Blueprint $table) {
            if (! Schema::hasColumn('gaji_tahap1', 'premi')) {
                $table->unsignedBigInteger('premi')->default(0)->after('tunjangan');
            }

            if (! Schema::hasColumn('gaji_tahap1', 'premi_breakdown')) {
                $table->json('premi_breakdown')->nullable()->after('premi');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gaji_tahap1')) {
            return;
        }

        Schema::table('gaji_tahap1', function (Blueprint $table) {
            if (Schema::hasColumn('gaji_tahap1', 'premi_breakdown')) {
                $table->dropColumn('premi_breakdown');
            }

            if (Schema::hasColumn('gaji_tahap1', 'premi')) {
                $table->dropColumn('premi');
            }
        });
    }
};
