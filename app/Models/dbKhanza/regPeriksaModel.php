<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class regPeriksaModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'reg_periksa';
    public $timestamps = false; // Matikan timestamps

}
