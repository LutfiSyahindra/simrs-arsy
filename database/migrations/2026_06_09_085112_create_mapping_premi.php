<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mapping_premi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jnsPremi_id')->constrained('master_jenis_premi')->cascadeOnDelete();
            $table->foreignId('jnsTindakan_id')->constrained('master_jenis_tindakan')->cascadeOnDelete();
            $table->integer('persentase');

            $table->timestamps();

            $table->unique(
                ['jnsPremi_id', 'jnsTindakan_id'],
                'mapping_premi_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_premi');
    }
};
