<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Model;

class admisiModel extends Model
{
    // Tentukan nama tabel jika tidak sesuai dengan konvensi Laravel
    protected $table = 'anjungan_admisi';

    // Jika tabel tidak memiliki kolom created_at dan updated_at
    public $timestamps = false;

    // Izinkan mass assignment untuk kolom-kolom berikut
    protected $fillable = [
        'no_antrian',
        'tanggal',
        'status_panggil',
        'loket',
        'created_at',
        'updated_at',
    ];
}
