<?php

namespace App\Models\dbSimrs;

use App\Models\dbKhanza\pegawaiModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class gajiTahap1Model extends Model
{
    use HasFactory;

    protected $table = 'gaji_tahap1';
    protected $fillable = ['nik', 'nama', 'jabatan', 'status', 'gaji_pokok', 'gaji_dibayar', 'tunjangan', 'periode'];

    public function pegawai()
    {
        return $this->belongsTo(pegawaiModel::class, 'nik', 'nik');
    }
}
