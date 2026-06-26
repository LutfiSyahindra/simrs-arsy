<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateGiziConfigPegawaiModel extends Model
{
    protected $table = 'generate_gizi_config_pegawai';

    protected $fillable = [
        'config_id',
        'role',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    protected $casts = [
        'config_id' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateGiziConfigModel::class, 'config_id');
    }
}
