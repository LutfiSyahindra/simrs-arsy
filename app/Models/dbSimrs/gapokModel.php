<?php

namespace App\Models\dbSimrs;

use App\Models\dbKhanza\pegawaiModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class gapokModel extends Model
{
    use HasFactory;

    protected $table = 'gaji_pokok';
    protected $fillable = ['nik', 'nama', 'jbtn', 'stts_kerja', 'masa_kerja','mulai_kontrak','gaji_pokok', 'stts_aktif','no_telp'];

    public function pegawai()
    {
        return $this->belongsTo(pegawaiModel::class, 'nik', 'nik');
    }
}
