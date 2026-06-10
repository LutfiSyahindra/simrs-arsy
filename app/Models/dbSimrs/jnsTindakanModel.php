<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jnsTindakanModel extends Model
{
    use HasFactory;

    protected $table = 'master_jenis_tindakan';
    protected $fillable = ['kode','jenis'];

    public function mappingTindakan()
    {
        return $this->hasMany(mappingTindakanModel::class, 'jnsTindakan_id', 'id');
    }

    public function mappingPremi()
    {
        return $this->hasMany(mappingPremiModel::class, 'jnsTindakan_id', 'id');
    }
}
