<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateKamarModel extends Model
{
    protected $table = 'generate_kamar_inap';

    protected $fillable = [
        'jenis_kamar',
        'plotingPremi_id',
        'kode_ploting',
        'nama_ploting',
        'jumlah_kamar',
        'jumlah_lama_inap',
        'nominal_hitung',
        'total_lama_inap',
        'periode',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'plotingPremi_id' => 'integer',
        'jumlah_kamar' => 'integer',
        'jumlah_lama_inap' => 'integer',
        'nominal_hitung' => 'integer',
        'total_lama_inap' => 'integer',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateKamarDetailModel::class, 'generate_kamar_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function generateBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generate_by');
    }

    public function plotingPremi(): BelongsTo
    {
        return $this->belongsTo(plotingPremiModel::class, 'plotingPremi_id');
    }
}
