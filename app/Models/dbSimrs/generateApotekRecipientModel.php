<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateApotekRecipientModel extends Model
{
    protected $table = 'generate_apotek_recipient';

    protected $fillable = [
        'generate_apotek_id',
        'role',
        'role_label',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
        'allocation_percent',
        'divider',
        'pool_total',
        'amount_per_recipient',
        'total_received',
    ];

    protected $casts = [
        'generate_apotek_id' => 'integer',
        'allocation_percent' => 'float',
        'divider' => 'float',
        'pool_total' => 'integer',
        'amount_per_recipient' => 'integer',
        'total_received' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generateApotekModel::class, 'generate_apotek_id');
    }
}
