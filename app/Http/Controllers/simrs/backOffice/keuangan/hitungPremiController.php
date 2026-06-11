<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class hitungPremiController extends Controller
{
    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.hitungPremi');
    }
}
