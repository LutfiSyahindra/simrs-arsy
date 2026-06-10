<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mappingPremiModel extends Model
{
    use HasFactory;

    protected $table = 'mapping_premi';
    protected $fillable = [
        'jnsPremi_id',
        'jnsTindakan_id',
        'persentase'
    ];

    public function jnsTindakan()
    {
        return $this->belongsTo(jnsTindakanModel::class, 'jnsTindakan_id', 'id');
    }

    public function jnsPremi()
    {
        return $this->belongsTo(jnsPremiModel::class, 'jnsPremi_id', 'id');
    }
}
