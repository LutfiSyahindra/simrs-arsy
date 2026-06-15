<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class premiPelayananNonMedisDetailModel extends Model
{
    protected $table = 'premi_pelayanan_non_medis_detail';

    protected $fillable = [
        'premi_pelayanan_non_medis_id',
        'mapping_premi_id',
        'jnsPremi_id',
        'jnsTindakan_id',
        'kode_premi',
        'nama_premi',
        'kode_jenis_tindakan',
        'nama_jenis_tindakan',
        'jenis_mapping',
        'nilai_mapping',
        'jumlah_data',
        'total_biaya_rawat',
        'dasar_hitung',
        'hasil_mapping',
        'data_tindakan',
    ];

    protected $casts = [
        'nilai_mapping' => 'decimal:2',
        'jumlah_data' => 'integer',
        'total_biaya_rawat' => 'decimal:2',
        'dasar_hitung' => 'decimal:2',
        'hasil_mapping' => 'decimal:2',
        'data_tindakan' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(
            premiPelayananNonMedisModel::class,
            'premi_pelayanan_non_medis_id'
        );
    }
}
