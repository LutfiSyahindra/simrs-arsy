<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class poliModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'poliklinik';

      public static function getPoli(){
        $poli = DB::connection('mysql_khanza')
        ->table('poliklinik')
        ->select('nm_poli', 'kd_poli')
        ->where('status', '=', '1')
        ->get();
        return $poli;
      }
}
