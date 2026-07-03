<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_vk_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('generate_vk_configs', 'distribution_mode')) {
                $table->string('distribution_mode', 20)->default('rata')->after('bpjs_pembagi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generate_vk_configs', function (Blueprint $table) {
            if (Schema::hasColumn('generate_vk_configs', 'distribution_mode')) {
                $table->dropColumn('distribution_mode');
            }
        });
    }
};
