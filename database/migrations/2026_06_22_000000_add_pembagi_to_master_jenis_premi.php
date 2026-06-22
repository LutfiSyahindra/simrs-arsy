<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jenis_premi', function (Blueprint $table) {
            $table->unsignedInteger('pembagi')->default(1)->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('master_jenis_premi', function (Blueprint $table) {
            $table->dropColumn('pembagi');
        });
    }
};
