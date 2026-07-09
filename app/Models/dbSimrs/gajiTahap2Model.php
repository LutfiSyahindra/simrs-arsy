<?php

namespace App\Models\dbSimrs;

use App\Models\dbKhanza\pegawaiModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class gajiTahap2Model extends Model
{
    use HasFactory;

    protected $table = 'gaji_tahap2';

    protected $fillable = [
        'periode',
        'nik',
        'nama',
        'jabatan',
        'status',
        'gaji_pokok',
        'gaji_dibayar',
        'total_premi',
        'total',
        'jumlah_sumber_premi',
        'premi_breakdown',
    ];

    protected $casts = [
        'gaji_pokok' => 'integer',
        'gaji_dibayar' => 'integer',
        'total_premi' => 'integer',
        'total' => 'integer',
        'jumlah_sumber_premi' => 'integer',
        'premi_breakdown' => 'array',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(gajiTahap2DetailModel::class, 'gaji_tahap2_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(pegawaiModel::class, 'nik', 'nik');
    }
}
