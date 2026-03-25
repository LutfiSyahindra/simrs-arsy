<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class pegawaiModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'pegawai';
    public $timestamps = false;
}
