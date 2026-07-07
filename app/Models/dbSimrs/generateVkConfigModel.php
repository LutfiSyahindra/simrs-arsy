<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateVkConfigModel extends Model
{
    protected $table = 'generate_vk_configs';

    protected $fillable = [
        'jenis_vk',
        'bpjs_percent',
        'bpjs_pembagi',
        'premi_bersama_percent',
        'distribution_mode',
    ];

    protected $casts = [
        'bpjs_percent' => 'float',
        'bpjs_pembagi' => 'integer',
        'premi_bersama_percent' => 'float',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateVkConfigPegawaiModel::class, 'config_id');
    }
}
