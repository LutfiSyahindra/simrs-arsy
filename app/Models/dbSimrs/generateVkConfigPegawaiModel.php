<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateVkConfigPegawaiModel extends Model
{
    protected $table = 'generate_vk_config_pegawai';

    protected $fillable = [
        'config_id',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateVkConfigModel::class, 'config_id');
    }
}
