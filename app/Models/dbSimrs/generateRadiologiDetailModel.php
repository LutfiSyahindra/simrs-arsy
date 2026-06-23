<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateRadiologiDetailModel extends Model
{
    protected $table = 'generate_radiologi_detail';

    protected $fillable = [
        'generate_radiologi_id',
        'role',
        'role_label',
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
        return $this->belongsTo(generateRadiologiModel::class, 'generate_radiologi_id');
    }
}
