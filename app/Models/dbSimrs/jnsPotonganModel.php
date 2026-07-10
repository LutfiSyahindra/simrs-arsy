<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jnsPotonganModel extends Model
{
    use HasFactory;

    protected $table = 'master_potongan';

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'nilai',
        'keterangan',
    ];
}
