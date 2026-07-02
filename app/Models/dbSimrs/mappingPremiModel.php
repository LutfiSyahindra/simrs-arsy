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
        'nilai',
        'jenis',
        'jenis_umum',
        'jenis_bpjs',
        'nilai_umum',
        'nilai_bpjs',
        'nilai_bersama_umum',
        'nilai_bersama_bpjs',
    ];

    protected $casts = [
        'nilai' => 'integer',
        'nilai_umum' => 'integer',
        'nilai_bpjs' => 'integer',
        'nilai_bersama_umum' => 'integer',
        'nilai_bersama_bpjs' => 'integer',
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
