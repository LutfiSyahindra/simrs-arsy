<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class TindakanRadModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'jns_perawatan_radiologi';
    public $timestamps = false;
}
