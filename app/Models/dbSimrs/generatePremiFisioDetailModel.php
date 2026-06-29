<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generatePremiFisioDetailModel extends Model
{
    protected $table = 'generate_premi_fisio_detail';

    protected $fillable = [
        'generate_premi_fisio_id',
        'detail_type',
        'source_label',
        'tindakan_config_id',
        'nama_tindakan',
        'harga_tindakan',
        'jumlah',
        'subtotal',
        'role',
        'role_label',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
        'allocation_percent',
        'basis_amount',
        'total_received',
    ];

    protected $casts = [
        'harga_tindakan' => 'integer',
        'jumlah' => 'integer',
        'subtotal' => 'integer',
        'allocation_percent' => 'float',
        'basis_amount' => 'integer',
        'total_received' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generatePremiFisioModel::class, 'generate_premi_fisio_id');
    }
}
