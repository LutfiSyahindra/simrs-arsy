<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generatePremiBersamaDistributionModel extends Model
{
    protected $table = 'generate_premi_bersama_distribution';

    protected $fillable = [
        'generate_premi_bersama_id',
        'jnsPremi_id',
        'nik',
        'pegawai_name',
        'pegawai_position',
        'stts_kerja',
        'skor_pegawai',
        'total_skor',
        'allocation_percent',
        'grand_total',
        'total_received',
        'skor_detail',
    ];

    protected $casts = [
        'jnsPremi_id' => 'integer',
        'skor_pegawai' => 'decimal:2',
        'total_skor' => 'decimal:2',
        'allocation_percent' => 'decimal:4',
        'grand_total' => 'decimal:2',
        'total_received' => 'decimal:2',
        'skor_detail' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(generatePremiBersamaModel::class, 'generate_premi_bersama_id');
    }
}
