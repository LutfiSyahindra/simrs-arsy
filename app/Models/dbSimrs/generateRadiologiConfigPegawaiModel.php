<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateRadiologiConfigPegawaiModel extends Model
{
    protected $table = 'generate_radiologi_config_pegawai';

    protected $fillable = [
        'config_id',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateRadiologiConfigModel::class, 'config_id');
    }
}
