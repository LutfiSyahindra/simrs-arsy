<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;

class generatePremiDriverConfigModel extends Model
{
    protected $table = 'generate_premi_driver_configs';

    protected $fillable = [
        'premi_bersama_percent',
    ];

    protected $casts = [
        'premi_bersama_percent' => 'float',
    ];
}
