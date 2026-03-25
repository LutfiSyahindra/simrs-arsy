<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jnsTunjanganModel extends Model
{
    use HasFactory;

    protected $table = 'master_tunjangan';
    protected $fillable = ['kode', 'nama', 'persentase'];
}
