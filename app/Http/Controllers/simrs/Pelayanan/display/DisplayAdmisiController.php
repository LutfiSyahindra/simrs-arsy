<?php

namespace App\Http\Controllers\simrs\Pelayanan\display;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DisplayAdmisiController extends Controller
{
    public function index()
    {
        return view('simrs.pelayanan.display.displayAdmisi');
    }
}
