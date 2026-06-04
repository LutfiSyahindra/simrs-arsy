<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mappingTindakanModel extends Model
{
    use HasFactory;

    protected $table = 'mapping_tindakan';
    protected $fillable = [
        'jnsTindakan_id',
        'sumber_tindakan',
        'kd_tindakan',
        'nm_tindakan',
        'kd_pj',
        'nm_pj',
        'parent_kd_tindakan',
        'parent_nm_tindakan',
    ];

    public function jnsTindakan()
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id', 'id');
    }
}
