<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class slipGajiDeliveryLogModel extends Model
{
    use HasFactory;

    protected $table = 'slip_gaji_delivery_logs';

    protected $fillable = [
        'periode',
        'tahap',
        'channel',
        'status',
        'gaji_id',
        'nik',
        'nama',
        'jabatan',
        'contact',
        'message',
        'response_payload',
        'processed_at',
    ];

    protected $casts = [
        'tahap' => 'integer',
        'gaji_id' => 'integer',
        'response_payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
