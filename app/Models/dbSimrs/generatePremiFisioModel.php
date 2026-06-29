<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generatePremiFisioModel extends Model
{
    protected $table = 'generate_premi_fisio';

    protected $fillable = [
        'periode',
        'jenis_fisio',
        'kode_generate',
        'tindakan_config_id',
        'nama_tindakan',
        'harga_tindakan',
        'jumlah_tindakan',
        'jumlah_pasien',
        'grand_total',
        'total_petugas1',
        'total_petugas2',
        'total_premi_bersama',
        'total_dibagikan',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'harga_tindakan' => 'integer',
        'jumlah_tindakan' => 'integer',
        'jumlah_pasien' => 'integer',
        'grand_total' => 'integer',
        'total_petugas1' => 'integer',
        'total_petugas2' => 'integer',
        'total_premi_bersama' => 'integer',
        'total_dibagikan' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generatePremiFisioDetailModel::class, 'generate_premi_fisio_id');
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
