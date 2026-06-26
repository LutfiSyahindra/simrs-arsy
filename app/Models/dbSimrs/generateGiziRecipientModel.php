<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateGiziRecipientModel extends Model
{
    protected $table = 'generate_gizi_recipient';

    protected $fillable = [
        'generate_gizi_id',
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
        'generate_gizi_id' => 'integer',
        'allocation_percent' => 'float',
        'pool_total' => 'integer',
        'total_received' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateGiziModel::class, 'generate_gizi_id');
    }
}
