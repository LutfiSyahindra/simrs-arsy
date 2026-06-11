<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('periode');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->foreignId('locked_by')
                ->nullable()
                ->after('locked_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('generate_bhp', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn(['is_locked', 'locked_at']);
        });
    }
};
