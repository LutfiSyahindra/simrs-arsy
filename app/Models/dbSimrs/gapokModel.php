<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class gapokModel extends Model
{
    use HasFactory;

    protected $table = 'gaji_pokok';
    protected $fillable = ['nik', 'nama', 'jbtn', 'stts_kerja', 'masa_kerja','mulai_kerja','gaji_pokok'];
}
