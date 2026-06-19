<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateOperasiDetailModel extends Model
{
    protected $table = 'generate_operasi_details';

    protected $fillable = [
        'generate_operasi_id',
        'role',
        'role_label',
        'pegawai_source',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
        'allocation_percent',
        'pool_total',
        'total_received',
    ];

    protected $casts = [
        'allocation_percent' => 'float',
        'pool_total' => 'integer',
        'total_received' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateOperasiModel::class, 'generate_operasi_id');
    }
}
