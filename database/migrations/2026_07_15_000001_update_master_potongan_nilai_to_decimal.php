<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('master_potongan')) {
            return;
        }

        if (! Schema::hasColumn('master_potongan', 'nilai')) {
            Schema::table('master_potongan', function (Blueprint $table) {
                $table->decimal('nilai', 15, 2)->nullable()->after('tipe');
            });

            return;
        }

        Schema::table('master_potongan', function (Blueprint $table) {
            $table->decimal('nilai', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        //
    }
};
