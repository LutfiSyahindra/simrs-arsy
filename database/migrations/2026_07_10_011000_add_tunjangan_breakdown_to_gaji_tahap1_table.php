<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gaji_tahap1')
            || Schema::hasColumn('gaji_tahap1', 'tunjangan_breakdown')) {
            return;
        }

        Schema::table('gaji_tahap1', function (Blueprint $table) {
            $table->json('tunjangan_breakdown')->nullable()->after('tunjangan');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gaji_tahap1')
            || ! Schema::hasColumn('gaji_tahap1', 'tunjangan_breakdown')) {
            return;
        }

        Schema::table('gaji_tahap1', function (Blueprint $table) {
            $table->dropColumn('tunjangan_breakdown');
        });
    }
};
