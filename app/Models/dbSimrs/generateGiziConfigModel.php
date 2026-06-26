<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateGiziConfigModel extends Model
{
    protected $table = 'generate_gizi_configs';

    protected $fillable = [
        'jenis_gizi',
        'source_period_mode',
        'jnsTindakan_id',
        'konsul_pegawai_percent',
        'konsul_premi_bersama_percent',
        'diit_petugas_percent',
        'diit_petugas_divider',
        'diit_premi_bersama_percent',
        'diit_premi_bersama_enabled',
    ];

    protected $casts = [
        'jnsTindakan_id' => 'integer',
        'konsul_pegawai_percent' => 'float',
        'konsul_premi_bersama_percent' => 'float',
        'diit_petugas_percent' => 'float',
        'diit_petugas_divider' => 'integer',
        'diit_premi_bersama_percent' => 'float',
        'diit_premi_bersama_enabled' => 'boolean',
    ];

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(generateGiziConfigMappingModel::class, 'config_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateGiziConfigPegawaiModel::class, 'config_id');
    }
}
