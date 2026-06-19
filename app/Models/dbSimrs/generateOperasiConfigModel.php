<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class generateOperasiConfigModel extends Model
{
    protected $table = 'generate_operasi_configs';

    protected $fillable = [
        'jenis_operasi',
        'instrumen_percent',
        'instrumen_premi_bersama_percent',
        'instrumen_petugas_percent',
        'instrumen_petugas_kelompok_20_percent',
        'instrumen_petugas_kelompok_80_percent',
        'dokter_anastesi_percent',
        'perawat_anastesi_percent',
        'perawat_anastesi_petugas_percent',
        'perawat_anastesi_premi_bersama_percent',
    ];

    protected $casts = [
        'instrumen_percent' => 'float',
        'instrumen_premi_bersama_percent' => 'float',
        'instrumen_petugas_percent' => 'float',
        'instrumen_petugas_kelompok_20_percent' => 'float',
        'instrumen_petugas_kelompok_80_percent' => 'float',
        'dokter_anastesi_percent' => 'float',
        'perawat_anastesi_percent' => 'float',
        'perawat_anastesi_petugas_percent' => 'float',
        'perawat_anastesi_premi_bersama_percent' => 'float',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(generateOperasiConfigPegawaiModel::class, 'config_id');
    }
}
