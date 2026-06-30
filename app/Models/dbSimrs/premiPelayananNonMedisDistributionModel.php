<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class premiPelayananNonMedisDistributionModel extends Model
{
    protected $table = 'premi_pelayanan_non_medis_distribution';

    protected $fillable = [
        'premi_pelayanan_non_medis_id',
        'jnsPremi_id',
        'nik',
        'pegawai_name',
        'pegawai_position',
        'distribution_mode',
        'total_final',
        'jumlah_penerima',
        'total_diterima',
    ];

    protected $casts = [
        'jnsPremi_id' => 'integer',
        'total_final' => 'decimal:2',
        'jumlah_penerima' => 'integer',
        'total_diterima' => 'decimal:2',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(
            premiPelayananNonMedisModel::class,
            'premi_pelayanan_non_medis_id'
        );
    }
}
