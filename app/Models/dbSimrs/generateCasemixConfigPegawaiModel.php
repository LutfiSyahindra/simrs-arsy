<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateCasemixConfigPegawaiModel extends Model
{
    protected $table = 'generate_casemix_config_pegawai';

    protected $fillable = [
        'config_id',
        'role',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateCasemixConfigModel::class, 'config_id');
    }
}
