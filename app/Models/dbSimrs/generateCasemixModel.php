<?php

namespace App\Models\dbSimrs;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateCasemixModel extends Model
{
    protected $table = 'generate_casemix';

    protected $fillable = [
        'periode',
        'biaya_rs',
        'tarif_bpjs',
        'verifikasi_hasil_bpjs',
        'kerugian_awal_percent',
        'total_score',
        'max_score',
        'score_percent',
        'reward_percent',
        'total_reward',
        'team_pool_percent',
        'team_pool',
        'non_team_pool',
        'leader_total',
        'kanit_total',
        'inputer_total',
        'total_dibagikan',
        'config_snapshot',
        'is_locked',
        'locked_at',
        'locked_by',
        'generate_by',
    ];

    protected $casts = [
        'biaya_rs' => 'integer',
        'tarif_bpjs' => 'integer',
        'verifikasi_hasil_bpjs' => 'integer',
        'kerugian_awal_percent' => 'float',
        'total_score' => 'integer',
        'max_score' => 'integer',
        'score_percent' => 'float',
        'reward_percent' => 'float',
        'total_reward' => 'integer',
        'team_pool_percent' => 'float',
        'team_pool' => 'integer',
        'non_team_pool' => 'integer',
        'leader_total' => 'integer',
        'kanit_total' => 'integer',
        'inputer_total' => 'integer',
        'total_dibagikan' => 'integer',
        'config_snapshot' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(generateCasemixDetailModel::class, 'generate_casemix_id');
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
