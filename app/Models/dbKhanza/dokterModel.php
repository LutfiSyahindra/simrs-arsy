<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class dokterModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'dokter';

    public static function getDokter($kd_poli) {
        return DB::connection('mysql_khanza')
            ->table('dokter')
            ->where('kd_sps', $kd_poli)
            ->get();
    }
}
