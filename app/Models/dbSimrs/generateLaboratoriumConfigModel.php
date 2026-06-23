<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateLaboratoriumConfigModel extends Model
{
    protected $table = 'generate_laboratorium_configs';

    protected $fillable = [
        'jenis_laboratorium',
        'petugas_mode',
        'petugas_percent',
        'petugas_nominal',
        'bpjs_petugas_divider',
        'bersama_mode',
        'bersama_percent',
        'bersama_nominal',
    ];

    protected $casts = [
        'petugas_percent' => 'float',
        'petugas_nominal' => 'integer',
        'bpjs_petugas_divider' => 'integer',
        'bersama_percent' => 'float',
        'bersama_nominal' => 'integer',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateLaboratoriumConfigPegawaiModel::class, 'config_id');
    }
}
