<?php

namespace App\Http\Controllers\simrs\Pelayanan\display;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PoliWsController extends Controller
{
    public function index()
    {
        return view('simrs.pelayanan.display.displayPoliWS');
    }
}
