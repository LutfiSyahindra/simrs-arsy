<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateTindakanMedisDistributionModel extends Model
{
    protected $table = 'generate_tindakan_medis_distribution';

    protected $fillable = [
        'generate_tindakan_medis_id',
        'jnsPremi_id',
        'nik',
        'pegawai_name',
        'pegawai_position',
        'distribution_mode',
        'total_final',
        'jumlah_penerima',
        'total_dasar',
        'has_icu_bonus',
        'total_icu',
        'icu_bonus_info',
        'has_nicu_bonus',
        'total_nicu',
        'nicu_bonus_info',
        'total_diterima',
    ];

    protected $casts = [
        'jnsPremi_id' => 'integer',
        'total_final' => 'decimal:2',
        'jumlah_penerima' => 'integer',
        'total_dasar' => 'decimal:2',
        'has_icu_bonus' => 'boolean',
        'total_icu' => 'decimal:2',
        'icu_bonus_info' => 'array',
        'has_nicu_bonus' => 'boolean',
        'total_nicu' => 'decimal:2',
        'nicu_bonus_info' => 'array',
        'total_diterima' => 'decimal:2',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(
            generateTindakanMedisModel::class,
            'generate_tindakan_medis_id'
        );
    }
}
