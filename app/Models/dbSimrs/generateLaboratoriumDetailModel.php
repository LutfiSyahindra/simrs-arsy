<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateLaboratoriumDetailModel extends Model
{
    protected $table = 'generate_laboratorium_detail';

    protected $fillable = [
        'generate_laboratorium_id',
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
        return $this->belongsTo(generateLaboratoriumModel::class, 'generate_laboratorium_id');
    }
}
