<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gaji_pokok') || Schema::hasColumn('gaji_pokok', 'email')) {
            return;
        }

        $hasNoTelpColumn = Schema::hasColumn('gaji_pokok', 'no_telp');

        Schema::table('gaji_pokok', function (Blueprint $table) use ($hasNoTelpColumn) {
            $column = $table->string('email')->nullable();

            if ($hasNoTelpColumn) {
                $column->after('no_telp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gaji_pokok') || ! Schema::hasColumn('gaji_pokok', 'email')) {
            return;
        }

        Schema::table('gaji_pokok', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
