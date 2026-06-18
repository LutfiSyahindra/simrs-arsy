<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateUgdModel extends Model
{
    protected $table = 'generate_ugd';

    protected $fillable = [
        'periode',
        'jenis_ugd',
        'kd_dokter',
        'nm_dokter',
        'plotingPremi_id',
        'kode_ploting',
        'nama_ploting',
        'jumlah_pasien',
        'nominal_hitung',
        'total_ugd',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'plotingPremi_id' => 'integer',
        'jumlah_pasien' => 'integer',
        'nominal_hitung' => 'integer',
        'total_ugd' => 'integer',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function plotingPremi(): BelongsTo
    {
        return $this->belongsTo(plotingPremiModel::class, 'plotingPremi_id');
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
