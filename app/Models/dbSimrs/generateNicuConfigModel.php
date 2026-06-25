<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateNicuConfigModel extends Model
{
    protected $table = 'generate_nicu_configs';

    protected $fillable = [
        'jenis_nicu',
        'jnsTindakan_id',
        'perawat_nicu_percent',
        'pegawai_nicu_khusus_percent',
        'perawat_nicu_reguler_percent',
        'perawat_nicu_divider',
        'premi_medis_percent',
        'premi_medis_divider',
        'premi_bersama_percent',
        'critical_action_name',
        'critical_action_names',
    ];

    protected $casts = [
        'jnsTindakan_id' => 'integer',
        'perawat_nicu_percent' => 'float',
        'pegawai_nicu_khusus_percent' => 'float',
        'perawat_nicu_reguler_percent' => 'float',
        'perawat_nicu_divider' => 'integer',
        'premi_medis_percent' => 'float',
        'premi_medis_divider' => 'integer',
        'premi_bersama_percent' => 'float',
        'critical_action_names' => 'array',
    ];

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }

    public function tindakan(): HasMany
    {
        return $this->hasMany(generateNicuConfigTindakanModel::class, 'config_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateNicuConfigPegawaiModel::class, 'config_id');
    }
}


