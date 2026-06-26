<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateGiziDetailModel extends Model
{
    protected $table = 'generate_gizi_detail';

    protected $fillable = [
        'generate_gizi_id',
        'mapping_tindakan_id',
        'jnsTindakan_id',
        'kelompok',
        'source_table',
        'sumber_tindakan',
        'no_rawat',
        'no_rkm_medis',
        'nm_pasien',
        'kd_pj',
        'nama_penjamin',
        'tanggal',
        'jam',
        'kd_tindakan',
        'nm_tindakan',
        'kd_dokter',
        'nm_dokter',
        'nip',
        'nama_petugas',
        'biaya_rawat',
    ];

    protected $casts = [
        'generate_gizi_id' => 'integer',
        'mapping_tindakan_id' => 'integer',
        'jnsTindakan_id' => 'integer',
        'tanggal' => 'date',
        'biaya_rawat' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateGiziModel::class, 'generate_gizi_id');
    }
}
