<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class TindakanOperasiModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'paket_operasi';
    public $timestamps = false;
}
