<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('penggajian_rounding_config')) {
            Schema::create('penggajian_rounding_config', function (Blueprint $table) {
                $table->id();
                $table->boolean('premium_received_enabled')->default(false);
                $table->unsignedInteger('premium_received_base')->default(1000);
                $table->string('premium_received_mode', 20)->default('up');
                $table->boolean('stage1_total_enabled')->default(true);
                $table->unsignedInteger('stage1_total_base')->default(1000);
                $table->string('stage1_total_mode', 20)->default('up');
                $table->boolean('stage2_total_enabled')->default(true);
                $table->unsignedInteger('stage2_total_base')->default(1000);
                $table->string('stage2_total_mode', 20)->default('up');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('gaji_tahap1')) {
            if (! Schema::hasColumn('gaji_tahap1', 'pembulatan')) {
                Schema::table('gaji_tahap1', function (Blueprint $table) {
                    $table->bigInteger('pembulatan')->default(0)->after('tunjangan');
                });
            }

            if (! Schema::hasColumn('gaji_tahap1', 'total')) {
                Schema::table('gaji_tahap1', function (Blueprint $table) {
                    $table->unsignedBigInteger('total')->default(0)->after('pembulatan');
                });
            }
        }

        if (Schema::hasTable('gaji_tahap2')) {
            if (! Schema::hasColumn('gaji_tahap2', 'pembulatan')) {
                $afterColumn = Schema::hasColumn('gaji_tahap2', 'total_potongan')
                    ? 'total_potongan'
                    : 'total_premi';

                Schema::table('gaji_tahap2', function (Blueprint $table) use ($afterColumn) {
                    $table->bigInteger('pembulatan')->default(0)->after($afterColumn);
                });
            }
        }

        if (Schema::hasTable('gaji_tahap2_detail')) {
            if (! Schema::hasColumn('gaji_tahap2_detail', 'source_periode')) {
                Schema::table('gaji_tahap2_detail', function (Blueprint $table) {
                    $table->string('source_periode', 7)->nullable()->after('source_id');
                });
            }

            if (! Schema::hasColumn('gaji_tahap2_detail', 'source_period_mode')) {
                Schema::table('gaji_tahap2_detail', function (Blueprint $table) {
                    $table->string('source_period_mode', 20)->nullable()->after('source_periode');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gaji_tahap2_detail')) {
            Schema::table('gaji_tahap2_detail', function (Blueprint $table) {
                if (Schema::hasColumn('gaji_tahap2_detail', 'source_period_mode')) {
                    $table->dropColumn('source_period_mode');
                }

                if (Schema::hasColumn('gaji_tahap2_detail', 'source_periode')) {
                    $table->dropColumn('source_periode');
                }
            });
        }

        if (Schema::hasTable('gaji_tahap2')) {
            Schema::table('gaji_tahap2', function (Blueprint $table) {
                if (Schema::hasColumn('gaji_tahap2', 'pembulatan')) {
                    $table->dropColumn('pembulatan');
                }
            });
        }

        if (Schema::hasTable('gaji_tahap1')) {
            Schema::table('gaji_tahap1', function (Blueprint $table) {
                if (Schema::hasColumn('gaji_tahap1', 'total')) {
                    $table->dropColumn('total');
                }

                if (Schema::hasColumn('gaji_tahap1', 'pembulatan')) {
                    $table->dropColumn('pembulatan');
                }
            });
        }

        Schema::dropIfExists('penggajian_rounding_config');
    }
};
