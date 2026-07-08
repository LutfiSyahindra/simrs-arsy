<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generatePremiDokterDetailModel extends Model
{
    protected $table = 'generate_premi_dokter_detail';

    protected $fillable = [
        'generate_premi_dokter_id',
        'kd_dokter',
        'nm_dokter',
        'kd_sps',
        'nm_sps',
        'kategori',
        'percent',
        'jumlah_data',
        'jumlah_pasien',
        'total_biaya_rawat',
        'grand_total',
        'total_premi',
        'source_breakdown',
        'action_breakdown',
        'data_rawat',
    ];

    protected $casts = [
        'percent' => 'float',
        'jumlah_data' => 'integer',
        'jumlah_pasien' => 'integer',
        'total_biaya_rawat' => 'float',
        'grand_total' => 'float',
        'total_premi' => 'float',
        'source_breakdown' => 'array',
        'action_breakdown' => 'array',
        'data_rawat' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(generatePremiDokterModel::class, 'generate_premi_dokter_id');
    }
}
