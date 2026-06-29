<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;

class generatePremiFisioConfigTindakanModel extends Model
{
    protected $table = 'generate_premi_fisio_config_tindakan';

    protected $fillable = [
        'kode_tindakan',
        'nama_tindakan',
        'harga',
        'is_active',
        'sort_order',
        'note',
    ];

    protected $casts = [
        'harga' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
