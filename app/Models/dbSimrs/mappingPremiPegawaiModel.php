<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mappingPremiPegawaiModel extends Model
{
    use HasFactory;

    protected $table = 'mapping_premi_pegawai';

    protected $fillable = [
        'jnsPremi_id',
        'nik',
    ];

    public function jnsPremi()
    {
        return $this->belongsTo(jnsPremiModel::class, 'jnsPremi_id', 'id');
    }

    public function gapok()
    {
        return $this->belongsTo(gapokModel::class, 'nik', 'nik');
    }
}
