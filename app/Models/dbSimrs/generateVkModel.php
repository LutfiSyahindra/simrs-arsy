<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class generateVkModel extends Model
{
    protected $table = 'generate_vk';

    protected $fillable = [
        'periode',
        'jenis_vk',
        'source_key',
        'sumber_tindakan',
        'kd_tindakan',
        'nm_tindakan',
        'kd_pj',
        'nm_pj',
        'parent_kd_tindakan',
        'parent_nm_tindakan',
        'plotingPremi_id',
        'kode_ploting',
        'nama_ploting',
        'jumlah_tindakan',
        'nominal_hitung',
        'total_vk',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'plotingPremi_id' => 'integer',
        'jumlah_tindakan' => 'integer',
        'nominal_hitung' => 'integer',
        'total_vk' => 'integer',
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
