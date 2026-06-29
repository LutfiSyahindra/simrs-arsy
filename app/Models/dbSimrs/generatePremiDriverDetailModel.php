<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generatePremiDriverDetailModel extends Model
{
    protected $table = 'generate_premi_driver_detail';

    protected $fillable = [
        'generate_premi_driver_id',
        'tujuan_id',
        'kode_tujuan',
        'nama_tujuan',
        'harga',
        'jumlah',
        'subtotal',
    ];

    protected $casts = [
        'tujuan_id' => 'integer',
        'harga' => 'integer',
        'jumlah' => 'integer',
        'subtotal' => 'integer',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(generatePremiDriverModel::class, 'generate_premi_driver_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(generatePremiDriverTujuanModel::class, 'tujuan_id');
    }
}
