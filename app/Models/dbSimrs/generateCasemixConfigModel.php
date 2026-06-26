<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateCasemixConfigModel extends Model
{
    protected $table = 'generate_casemix_configs';

    protected $fillable = [
        'config_key',
        'excellent_min_percent',
        'excellent_reward_percent',
        'good_min_percent',
        'good_reward_percent',
        'low_reward_percent',
        'team_pool_percent',
        'leader_percent',
        'kanit_percent',
        'inputer_percent',
        'inputer_divider',
        'question_config',
    ];

    protected $casts = [
        'excellent_min_percent' => 'float',
        'excellent_reward_percent' => 'float',
        'good_min_percent' => 'float',
        'good_reward_percent' => 'float',
        'low_reward_percent' => 'float',
        'team_pool_percent' => 'float',
        'leader_percent' => 'float',
        'kanit_percent' => 'float',
        'inputer_percent' => 'float',
        'inputer_divider' => 'integer',
        'question_config' => 'array',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateCasemixConfigPegawaiModel::class, 'config_id');
    }
}
