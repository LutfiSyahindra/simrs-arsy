<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class potonganPegawaiModel extends Model
{
    use HasFactory;

    protected $table = 'potongan_pegawai';

    protected $fillable = [
        'nik',
        'potongan_id',
        'nominal',
    ];

    public function jenisPotongan()
    {
        return $this->belongsTo(jnsPotonganModel::class, 'potongan_id', 'id');
    }

    public function gapok()
    {
        return $this->belongsTo(gapokModel::class, 'nik', 'nik');
    }
}
