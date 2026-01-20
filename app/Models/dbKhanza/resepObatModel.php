<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class resepObatModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'resep_obat';
    protected $primaryKey = 'no_resep';
}
