<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateApotekConfigModel extends Model
{
    protected $table = 'generate_apotek_configs';

    protected $fillable = [
        'jenis_apotek',
        'jnsTindakan_id',
        'tarif_per_item',
        'source_period_mode',
        'include_bpjs_in_umum',
        'jasa_farmasi_percent',
        'formula_31_percent',
        'formula_31_divider',
        'formula_7_percent',
        'formula_7_divider',
        'formula_12_percent',
        'formula_12_divider',
        'premi_bersama_percent',
    ];

    protected $casts = [
        'jnsTindakan_id' => 'integer',
        'tarif_per_item' => 'integer',
        'source_period_mode' => 'string',
        'include_bpjs_in_umum' => 'boolean',
        'jasa_farmasi_percent' => 'float',
        'formula_31_percent' => 'float',
        'formula_31_divider' => 'float',
        'formula_7_percent' => 'float',
        'formula_7_divider' => 'float',
        'formula_12_percent' => 'float',
        'formula_12_divider' => 'float',
        'premi_bersama_percent' => 'float',
    ];

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(generateApotekConfigMappingModel::class, 'config_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateApotekConfigPegawaiModel::class, 'config_id');
    }
}
