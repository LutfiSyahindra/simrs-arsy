<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateRadiologiConfigModel extends Model
{
    protected $table = 'generate_radiologi_configs';

    protected $fillable = [
        'jenis_radiologi',
        'petugas_mode',
        'petugas_percent',
        'petugas_nominal',
        'bpjs_petugas_formula_rate',
        'bpjs_petugas_formula_divider',
        'bersama_mode',
        'bersama_percent',
        'bersama_nominal',
    ];

    protected $casts = [
        'petugas_percent' => 'float',
        'petugas_nominal' => 'integer',
        'bpjs_petugas_formula_rate' => 'float',
        'bpjs_petugas_formula_divider' => 'integer',
        'bersama_percent' => 'float',
        'bersama_nominal' => 'integer',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateRadiologiConfigPegawaiModel::class, 'config_id');
    }
}
