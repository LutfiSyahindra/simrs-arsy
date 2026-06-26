<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generate_casemix_configs')
            || Schema::hasColumn('generate_casemix_configs', 'inputer_divider')) {
            return;
        }

        Schema::table('generate_casemix_configs', function (Blueprint $table) {
            $table->unsignedInteger('inputer_divider')
                ->default(4)
                ->after('inputer_percent');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('generate_casemix_configs')
            || ! Schema::hasColumn('generate_casemix_configs', 'inputer_divider')) {
            return;
        }

        Schema::table('generate_casemix_configs', function (Blueprint $table) {
            $table->dropColumn('inputer_divider');
        });
    }
};
