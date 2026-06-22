<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jnsPremiModel extends Model
{
    use HasFactory;

    protected $table = 'master_jenis_premi';

    protected $fillable = ['kode', 'jenis', 'pembagi'];

    protected $casts = [
        'pembagi' => 'integer',
    ];

    public function mappingPremi()
    {
        return $this->hasMany(mappingPremiModel::class, 'jnsPremi_id', 'id');
    }

    public function pegawaiPremi()
    {
        return $this->hasMany(mappingPremiPegawaiModel::class, 'jnsPremi_id', 'id');
    }
}
