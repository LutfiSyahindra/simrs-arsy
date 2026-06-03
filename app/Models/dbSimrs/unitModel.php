<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unitModel extends Model
{
    use HasFactory;

    protected $table = 'master_unit';
    protected $fillable = ['kode','jenis', 'keterangan'];
}
