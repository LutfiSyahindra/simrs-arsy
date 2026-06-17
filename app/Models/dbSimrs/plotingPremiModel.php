<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class plotingPremiModel extends Model
{
    use HasFactory;

    protected $table = 'master_ploting_premi';

    protected $fillable = ['kode', 'ploting'];

}
