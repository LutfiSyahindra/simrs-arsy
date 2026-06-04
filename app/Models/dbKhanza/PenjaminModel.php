<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class PenjaminModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'penjab';
    public $timestamps = false;
}
