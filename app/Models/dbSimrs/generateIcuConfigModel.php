<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateIcuConfigModel extends Model
{
    protected $table = 'generate_icu_configs';

    protected $fillable = [
        'jenis_icu',
        'jnsTindakan_id',
        'perawat_icu_percent',
        'pegawai_icu_khusus_percent',
        'perawat_icu_reguler_percent',
        'perawat_icu_divider',
        'premi_medis_percent',
        'premi_medis_divider',
        'premi_bersama_percent',
        'critical_action_name',
        'critical_action_names',
    ];

    protected $casts = [
        'jnsTindakan_id' => 'integer',
        'perawat_icu_percent' => 'float',
        'pegawai_icu_khusus_percent' => 'float',
        'perawat_icu_reguler_percent' => 'float',
        'perawat_icu_divider' => 'integer',
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
        return $this->hasMany(generateIcuConfigTindakanModel::class, 'config_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateIcuConfigPegawaiModel::class, 'config_id');
    }
}
