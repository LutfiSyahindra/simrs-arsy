<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class gajiTahap2DetailModel extends Model
{
    use HasFactory;

    protected $table = 'gaji_tahap2_detail';

    protected $fillable = [
        'gaji_tahap2_id',
        'source_key',
        'source_label',
        'source_table',
        'source_id',
        'source_periode',
        'source_period_mode',
        'role_label',
        'nominal',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'nominal' => 'integer',
    ];

    public function gajiTahap2(): BelongsTo
    {
        return $this->belongsTo(gajiTahap2Model::class, 'gaji_tahap2_id');
    }
}
