<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateBhpDetailModel extends Model
{
    protected $table = 'generate_bhp_detail';

    protected $fillable = [
        'generate_bhp_id',
        'no_rawat',
        'tgl_registrasi',
        'kd_pj',
        'nama_penjamin',
    ];

    protected $casts = [
        'tgl_registrasi' => 'date',
    ];

    public function generateBhp(): BelongsTo
    {
        return $this->belongsTo(generateBhpModel::class, 'generate_bhp_id');
    }
}
