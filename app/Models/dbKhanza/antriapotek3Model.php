<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class antriapotek3Model extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'antriapotek3';
    public $timestamps = false; // Matikan timestamps

}
