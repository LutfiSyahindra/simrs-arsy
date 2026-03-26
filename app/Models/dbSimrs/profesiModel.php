<?php

namespace App\Models\dbSimrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class profesiModel extends Model
{
    use HasFactory;

    protected $table = 'profesis';
    protected $fillable = ['kode', 'nama', 'tunjangan'];
}
