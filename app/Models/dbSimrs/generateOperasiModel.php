<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateOperasiModel extends Model
{
    protected $table = 'generate_operasi';

    protected $fillable = [
        'periode',
        'jenis_operasi',
        'jumlah_pasien',
        'nominal_pengali',
        'total_operasi',
        'total_instrumen',
        'total_premi_bersama',
        'total_instrumen_petugas',
        'total_instrumen_kelompok_20',
        'total_instrumen_kelompok_80',
        'total_dokter_anastesi',
        'total_perawat_anastesi',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'total_operasi' => 'integer',
        'jumlah_pasien' => 'integer',
        'nominal_pengali' => 'integer',
        'total_instrumen' => 'integer',
        'total_premi_bersama' => 'integer',
        'total_instrumen_petugas' => 'integer',
        'total_instrumen_kelompok_20' => 'integer',
        'total_instrumen_kelompok_80' => 'integer',
        'total_dokter_anastesi' => 'integer',
        'total_perawat_anastesi' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateOperasiDetailModel::class, 'generate_operasi_id');
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
