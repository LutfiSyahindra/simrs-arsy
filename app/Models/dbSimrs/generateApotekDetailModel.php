<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateApotekDetailModel extends Model
{
    protected $table = 'generate_apotek_detail';

    protected $fillable = [
        'generate_apotek_id',
        'mapping_tindakan_id',
        'mapping_premi_id',
        'jnsTindakan_id',
        'source_table',
        'sumber_tindakan',
        'no_rawat',
        'no_rkm_medis',
        'nm_pasien',
        'kd_pj',
        'nama_penjamin',
        'tanggal',
        'jam',
        'kode_barang',
        'nama_barang',
        'qty',
        'harga_obat',
        'total_obat',
        'nominal_premi',
        'total_premi',
        'status',
        'kd_tindakan',
        'nm_tindakan',
        'kd_dokter',
        'nm_dokter',
        'nip',
        'nama_petugas',
        'biaya_rawat',
        'jenis_mapping',
        'nilai_mapping',
        'hasil_mapping',
    ];

    protected $casts = [
        'mapping_tindakan_id' => 'integer',
        'mapping_premi_id' => 'integer',
        'jnsTindakan_id' => 'integer',
        'tanggal' => 'date',
        'qty' => 'float',
        'harga_obat' => 'integer',
        'total_obat' => 'integer',
        'nominal_premi' => 'integer',
        'total_premi' => 'integer',
        'biaya_rawat' => 'integer',
        'nilai_mapping' => 'float',
        'hasil_mapping' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateApotekModel::class, 'generate_apotek_id');
    }
}
