<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generatePremiFisioConfigModel extends Model
{
    protected $table = 'generate_premi_fisio_configs';

    protected $fillable = [
        'jenis_fisio',
        'grand_mode',
        'grand_nominal',
        'petugas1_mode',
        'petugas1_percent',
        'petugas1_nominal',
        'petugas2_mode',
        'petugas2_percent',
        'petugas2_nominal',
        'bersama_mode',
        'bersama_percent',
        'bersama_nominal',
    ];

    protected $casts = [
        'grand_nominal' => 'integer',
        'petugas1_percent' => 'float',
        'petugas1_nominal' => 'integer',
        'petugas2_percent' => 'float',
        'petugas2_nominal' => 'integer',
        'bersama_percent' => 'float',
        'bersama_nominal' => 'integer',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(generatePremiFisioConfigPegawaiModel::class, 'config_id');
    }
}
