<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class TindakanLabModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'jns_perawatan_lab';
    public $timestamps = false;
}
