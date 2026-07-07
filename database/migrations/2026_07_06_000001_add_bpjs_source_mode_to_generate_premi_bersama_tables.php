<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_premi_bersama_configs')
            && ! Schema::hasColumn('generate_premi_bersama_configs', 'bpjs_source_mode')) {
            Schema::table('generate_premi_bersama_configs', function (Blueprint $table) {
                $table->string('bpjs_source_mode', 20)->default('previous')->after('bhp_plotingPremi_id');
            });
        }

        if (Schema::hasTable('generate_premi_bersama')) {
            Schema::table('generate_premi_bersama', function (Blueprint $table) {
                if (! Schema::hasColumn('generate_premi_bersama', 'bpjs_source_mode')) {
                    $table->string('bpjs_source_mode', 20)->default('previous')->after('source_periode');
                }

                if (! Schema::hasColumn('generate_premi_bersama', 'bpjs_source_periode')) {
                    $table->string('bpjs_source_periode', 7)->nullable()->after('bpjs_source_mode');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('generate_premi_bersama')) {
            Schema::table('generate_premi_bersama', function (Blueprint $table) {
                if (Schema::hasColumn('generate_premi_bersama', 'bpjs_source_periode')) {
                    $table->dropColumn('bpjs_source_periode');
                }

                if (Schema::hasColumn('generate_premi_bersama', 'bpjs_source_mode')) {
                    $table->dropColumn('bpjs_source_mode');
                }
            });
        }

        if (Schema::hasTable('generate_premi_bersama_configs')
            && Schema::hasColumn('generate_premi_bersama_configs', 'bpjs_source_mode')) {
            Schema::table('generate_premi_bersama_configs', function (Blueprint $table) {
                $table->dropColumn('bpjs_source_mode');
            });
        }
    }
};
