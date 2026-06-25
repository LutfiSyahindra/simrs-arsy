<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateIcuConfigPegawaiModel extends Model
{
    protected $table = 'generate_icu_config_pegawai';

    protected $fillable = [
        'config_id',
        'role',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateIcuConfigModel::class, 'config_id');
    }
}
