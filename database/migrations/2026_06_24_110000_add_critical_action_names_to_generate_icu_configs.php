<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_icu_configs', function (Blueprint $table) {
            $table->json('critical_action_names')
                ->nullable()
                ->after('critical_action_name');
        });

        DB::table('generate_icu_configs')
            ->select('id', 'critical_action_name')
            ->orderBy('id')
            ->get()
            ->each(function ($config) {
                $name = trim((string) $config->critical_action_name);

                DB::table('generate_icu_configs')
                    ->where('id', $config->id)
                    ->update([
                        'critical_action_names' => $name !== '' ? json_encode([$name]) : json_encode([]),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('generate_icu_configs', function (Blueprint $table) {
            $table->dropColumn('critical_action_names');
        });
    }
};
