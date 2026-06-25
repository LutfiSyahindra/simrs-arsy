<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateIcuDetailModel extends Model
{
    protected $table = 'generate_icu_detail';

    protected $fillable = [
        'generate_icu_id',
        'mapping_tindakan_id',
        'jnsTindakan_id',
        'source_table',
        'sumber_tindakan',
        'no_rawat',
        'no_rkm_medis',
        'nm_pasien',
        'kd_pj',
        'nama_penjamin',
        'kd_kamar_icu',
        'tgl_masuk_icu',
        'jam_masuk_icu',
        'tgl_keluar_icu',
        'jam_keluar_icu',
        'tanggal',
        'jam',
        'kd_tindakan',
        'nm_tindakan',
        'kd_dokter',
        'nm_dokter',
        'nip',
        'nama_petugas',
        'biaya_rawat',
        'is_in_icu_range',
        'is_critical_action',
    ];

    protected $casts = [
        'mapping_tindakan_id' => 'integer',
        'jnsTindakan_id' => 'integer',
        'tgl_masuk_icu' => 'date',
        'tgl_keluar_icu' => 'date',
        'tanggal' => 'date',
        'biaya_rawat' => 'integer',
        'is_in_icu_range' => 'boolean',
        'is_critical_action' => 'boolean',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateIcuModel::class, 'generate_icu_id');
    }
}
