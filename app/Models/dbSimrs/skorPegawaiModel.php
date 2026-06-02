<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class skorPegawaiModel extends Model
{
    use HasFactory;

    protected $table = 'skoring_pegawai';
    protected $fillable = ['nik','skor_id', 'bobot_skor'];

    public function skor()
    {
        return $this->belongsTo(skorModel::class, 'skor_id', 'id');
    }

    public function gapok()
    {
        return $this->belongsTo(gapokModel::class, 'nik', 'nik');
    }
}
