<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class TindakanRajalModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'jns_perawatan';
    public $timestamps = false;
}
