<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generatePremiFisioConfigPegawaiModel extends Model
{
    protected $table = 'generate_premi_fisio_config_pegawai';

    protected $fillable = [
        'config_id',
        'role',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generatePremiFisioConfigModel::class, 'config_id');
    }
}
