<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('generate_vk_configs')
            && ! Schema::hasColumn('generate_vk_configs', 'premi_bersama_percent')
        ) {
            Schema::table('generate_vk_configs', function (Blueprint $table) {
                $table->decimal('premi_bersama_percent', 8, 4)
                    ->default(20)
                    ->after('bpjs_pembagi');
            });
        }

        if (
            Schema::hasTable('generate_vk')
            && ! Schema::hasColumn('generate_vk', 'total_premi_bersama')
        ) {
            Schema::table('generate_vk', function (Blueprint $table) {
                $table->unsignedBigInteger('total_premi_bersama')
                    ->default(0)
                    ->after('total_dibagikan');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('generate_vk')
            && Schema::hasColumn('generate_vk', 'total_premi_bersama')
        ) {
            Schema::table('generate_vk', function (Blueprint $table) {
                $table->dropColumn('total_premi_bersama');
            });
        }

        if (
            Schema::hasTable('generate_vk_configs')
            && Schema::hasColumn('generate_vk_configs', 'premi_bersama_percent')
        ) {
            Schema::table('generate_vk_configs', function (Blueprint $table) {
                $table->dropColumn('premi_bersama_percent');
            });
        }
    }
};
