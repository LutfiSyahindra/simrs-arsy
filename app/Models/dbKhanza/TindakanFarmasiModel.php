<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class TindakanFarmasiModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'databarang';
    public $timestamps = false;
}
