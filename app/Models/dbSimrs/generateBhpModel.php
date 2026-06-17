<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateBhpModel extends Model
{
    protected $table = 'generate_bhp';

    protected $fillable = [
        'jenis_bhp',
        'plotingPremi_id',
        'kode_ploting',
        'nama_ploting',
        'jumlah_bhp',
        'nominal_hitung',
        'total_bhp',
        'periode',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'plotingPremi_id' => 'integer',
        'jumlah_bhp' => 'integer',
        'nominal_hitung' => 'integer',
        'total_bhp' => 'integer',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateBhpDetailModel::class, 'generate_bhp_id');
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
