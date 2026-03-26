<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jabatanModel extends Model
{
    use HasFactory;

    protected $table = 'jabatans';
    protected $fillable = ['kode', 'nama', 'tunjangan'];
}
