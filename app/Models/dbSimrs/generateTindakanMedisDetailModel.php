<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateTindakanMedisDetailModel extends Model
{
    protected $table = 'generate_tindakan_medis_detail';

    protected $fillable = [
        'generate_tindakan_medis_id',
        'mapping_premi_id',
        'jnsPremi_id',
        'jnsTindakan_id',
        'kode_premi',
        'nama_premi',
        'kode_jenis_tindakan',
        'nama_jenis_tindakan',
        'jenis_mapping',
        'nilai_mapping',
        'source_rules',
        'jumlah_data',
        'jumlah_data_icu',
        'jumlah_data_nicu',
        'total_biaya_rawat',
        'dasar_hitung',
        'hasil_mapping',
        'data_rawat',
    ];

    protected $casts = [
        'mapping_premi_id' => 'integer',
        'jnsPremi_id' => 'integer',
        'jnsTindakan_id' => 'integer',
        'nilai_mapping' => 'decimal:4',
        'source_rules' => 'array',
        'jumlah_data' => 'integer',
        'jumlah_data_icu' => 'integer',
        'jumlah_data_nicu' => 'integer',
        'total_biaya_rawat' => 'decimal:2',
        'dasar_hitung' => 'decimal:2',
        'hasil_mapping' => 'decimal:2',
        'data_rawat' => 'array',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(generateTindakanMedisModel::class, 'generate_tindakan_medis_id');
    }
}
