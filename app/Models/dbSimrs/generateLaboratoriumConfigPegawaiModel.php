<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateLaboratoriumConfigPegawaiModel extends Model
{
    protected $table = 'generate_laboratorium_config_pegawai';

    protected $fillable = [
        'config_id',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(generateLaboratoriumConfigModel::class, 'config_id');
    }
}
