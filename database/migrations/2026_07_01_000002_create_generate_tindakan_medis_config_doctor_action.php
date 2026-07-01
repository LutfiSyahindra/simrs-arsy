<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generate_tindakan_medis_config_doctor_action')) {
            $this->completeExistingTable();

            return;
        }

        Schema::create('generate_tindakan_medis_config_doctor_action', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('config_id');
            $table->unsignedBigInteger('jnsTindakan_id');
            $table->timestamps();

            $table->foreign('config_id', 'gtm_doc_action_config_fk')
                ->references('id')
                ->on('generate_tindakan_medis_configs')
                ->cascadeOnDelete();
            $table->foreign('jnsTindakan_id', 'gtm_doc_action_tindakan_fk')
                ->references('id')
                ->on('master_jenis_tindakan')
                ->cascadeOnDelete();
            $table->unique(
                ['config_id', 'jnsTindakan_id'],
                'gtm_doc_action_unique'
            );
            $table->index('jnsTindakan_id', 'gtm_doc_action_tindakan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generate_tindakan_medis_config_doctor_action');
    }

    private function completeExistingTable(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM generate_tindakan_medis_config_doctor_action'))
            ->pluck('Key_name');
        $foreigns = collect(DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL",
            ['generate_tindakan_medis_config_doctor_action']
        ))->pluck('CONSTRAINT_NAME');

        Schema::table('generate_tindakan_medis_config_doctor_action', function (Blueprint $table) use ($indexes) {
            if (! $indexes->contains('gtm_doc_action_unique')) {
                $table->unique(['config_id', 'jnsTindakan_id'], 'gtm_doc_action_unique');
            }

            if (! $indexes->contains('gtm_doc_action_tindakan_idx')) {
                $table->index('jnsTindakan_id', 'gtm_doc_action_tindakan_idx');
            }
        });

        if (! $foreigns->contains('gtm_doc_action_tindakan_fk')) {
            Schema::table('generate_tindakan_medis_config_doctor_action', function (Blueprint $table) {
                $table->foreign('jnsTindakan_id', 'gtm_doc_action_tindakan_fk')
                    ->references('id')
                    ->on('master_jenis_tindakan')
                    ->cascadeOnDelete();
            });
        }
    }
};
