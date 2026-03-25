<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tunjanganPegawaiModel extends Model
{
    use HasFactory;

    protected $table = 'tunjangan_pegawai';
    protected $fillable = ['nik', 'tunjangan_id', 'nominal'];

    // ✅ BENAR (default id)
    public function jenisTunjangan()
    {
        return $this->belongsTo(jnsTunjanganModel::class, 'tunjangan_id', 'id');
    }

    // 🔥 FIX UTAMA DI SINI
    public function gapok()
    {
        return $this->belongsTo(gapokModel::class, 'nik', 'nik');
    }
}
