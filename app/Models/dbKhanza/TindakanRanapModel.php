<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class TindakanRanapModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'jns_perawatan_inap';
    public $timestamps = false;
}
