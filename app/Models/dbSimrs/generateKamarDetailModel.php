<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateKamarDetailModel extends Model
{
    protected $table = 'generate_kamar_inap_detail';

    protected $fillable = [
        'generate_kamar_id',
        'no_rawat',
        'tgl_masuk',
        'jam_masuk',
        'kd_pj',
        'nama_penjamin',
        'kd_kamar',
        'lama',
    ];

    protected $casts = [
        'tgl_masuk' => 'date',
        'lama' => 'integer',
    ];

    public function generateKamar(): BelongsTo
    {
        return $this->belongsTo(generateKamarModel::class, 'generate_kamar_id');
    }
}
