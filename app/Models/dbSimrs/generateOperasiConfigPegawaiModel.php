<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateOperasiConfigPegawaiModel extends Model
{
    protected $table = 'generate_operasi_config_pegawai';

    protected $fillable = [
        'config_id',
        'role',
        'pegawai_source',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateOperasiConfigModel::class, 'config_id');
    }
}
