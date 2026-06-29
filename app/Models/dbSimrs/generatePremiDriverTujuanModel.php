<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;

class generatePremiDriverTujuanModel extends Model
{
    protected $table = 'generate_premi_driver_tujuan';

    protected $fillable = [
        'kode',
        'nama_tujuan',
        'harga',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'harga' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
