<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class skorModel extends Model
{
    use HasFactory;

    protected $table = 'master_skor';
    protected $fillable = ['kd_skor','jenis', 'keterangan', 'bobot_skor'];
}
