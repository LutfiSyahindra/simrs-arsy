<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;

class DetailTindakanLabModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'template_laboratorium';
    public $timestamps = false;
}
