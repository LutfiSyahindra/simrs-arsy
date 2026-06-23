<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateLaboratoriumModel extends Model
{
    protected $table = 'generate_laboratorium';

    protected $fillable = [
        'periode',
        'jenis_laboratorium',
        'jumlah_tindakan',
        'jumlah_pasien',
        'total_bagian_laborat',
        'total_bagian_rs',
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
        'total_bagian_laborat' => 'integer',
        'total_bagian_rs' => 'integer',
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
        return $this->hasMany(generateLaboratoriumDetailModel::class, 'generate_laboratorium_id');
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
