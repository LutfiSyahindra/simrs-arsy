<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unitPegawaiModel extends Model
{
    use HasFactory;

    protected $table = 'unit_pegawai';
    protected $fillable = ['nik','unit_id'];

    public function unit()
    {
        return $this->belongsTo(unitModel::class, 'unit_id', 'id');
    }

    public function gapok()
    {
        return $this->belongsTo(gapokModel::class, 'nik', 'nik');
    }
}
