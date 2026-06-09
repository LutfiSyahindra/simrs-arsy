<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jnsPremiModel extends Model
{
    use HasFactory;

    protected $table = 'master_jenis_premi';
    protected $fillable = ['kode','jenis'];

    // public function mappingTindakan()
    // {
    //     return $this->hasMany(mappingTindakanModel::class, 'jnsPremi_id', 'id');
    // }
}
