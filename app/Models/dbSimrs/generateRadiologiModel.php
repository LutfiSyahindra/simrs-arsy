<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateRadiologiModel extends Model
{
    protected $table = 'generate_radiologi';

    protected $fillable = [
        'periode',
        'jenis_radiologi',
        'jumlah_tindakan',
        'jumlah_pasien',
        'total_tarif_tindakan_petugas',
        'total_biaya',
        'total_manajemen',
        'total_premi_petugas',
        'total_premi_bersama',
        'total_dibagikan',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'jumlah_tindakan' => 'integer',
        'jumlah_pasien' => 'integer',
        'total_tarif_tindakan_petugas' => 'integer',
        'total_biaya' => 'integer',
        'total_manajemen' => 'integer',
        'total_premi_petugas' => 'integer',
        'total_premi_bersama' => 'integer',
        'total_dibagikan' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateRadiologiDetailModel::class, 'generate_radiologi_id');
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
