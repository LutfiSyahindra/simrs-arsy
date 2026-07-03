<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'total_vk_awal',
        'bpjs_pool',
        'total_dibagikan',
        'config_snapshot',
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
        'total_vk_awal' => 'integer',
        'bpjs_pool' => 'integer',
        'total_dibagikan' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateVkDetailModel::class, 'generate_vk_id');
    }

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
