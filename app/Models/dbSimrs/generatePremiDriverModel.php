<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generatePremiDriverModel extends Model
{
    protected $table = 'generate_premi_driver';

    protected $fillable = [
        'periode',
        'pegawai_id',
        'pegawai_name',
        'pegawai_position',
        'jumlah_tujuan',
        'total_jumlah',
        'grand_total',
        'premi_pegawai_percent',
        'total_premi_pegawai',
        'premi_bersama_percent',
        'total_premi_bersama',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'jumlah_tujuan' => 'integer',
        'total_jumlah' => 'integer',
        'grand_total' => 'integer',
        'premi_pegawai_percent' => 'float',
        'total_premi_pegawai' => 'integer',
        'premi_bersama_percent' => 'float',
        'total_premi_bersama' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generatePremiDriverDetailModel::class, 'generate_premi_driver_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function generateBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generate_by');
    }
}
