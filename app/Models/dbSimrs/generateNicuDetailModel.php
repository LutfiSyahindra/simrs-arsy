<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateNicuDetailModel extends Model
{
    protected $table = 'generate_nicu_detail';

    protected $fillable = [
        'generate_nicu_id',
        'mapping_tindakan_id',
        'jnsTindakan_id',
        'source_table',
        'sumber_tindakan',
        'no_rawat',
        'no_rkm_medis',
        'nm_pasien',
        'kd_pj',
        'nama_penjamin',
        'kd_kamar_nicu',
        'tgl_masuk_nicu',
        'jam_masuk_nicu',
        'tgl_keluar_nicu',
        'jam_keluar_nicu',
        'tanggal',
        'jam',
        'kd_tindakan',
        'nm_tindakan',
        'kd_dokter',
        'nm_dokter',
        'nip',
        'nama_petugas',
        'biaya_rawat',
        'is_in_nicu_range',
        'is_critical_action',
    ];

    protected $casts = [
        'mapping_tindakan_id' => 'integer',
        'jnsTindakan_id' => 'integer',
        'tgl_masuk_nicu' => 'date',
        'tgl_keluar_nicu' => 'date',
        'tanggal' => 'date',
        'biaya_rawat' => 'integer',
        'is_in_nicu_range' => 'boolean',
        'is_critical_action' => 'boolean',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateNicuModel::class, 'generate_nicu_id');
    }
}


