<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->foreignId('generate_by')
                ->nullable()
                ->after('locked_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generate_by');
        });
    }
};
