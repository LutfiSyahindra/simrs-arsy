<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateKamarConfigModel extends Model
{
    protected $table = 'generate_kamar_configs';

    protected $fillable = [
        'jenis_kamar',
        'plotingPremi_id',
        'default_nominal',
    ];

    protected $casts = [
        'plotingPremi_id' => 'integer',
        'default_nominal' => 'integer',
    ];

    public function plotingPremi(): BelongsTo
    {
        return $this->belongsTo(plotingPremiModel::class, 'plotingPremi_id');
    }
}
