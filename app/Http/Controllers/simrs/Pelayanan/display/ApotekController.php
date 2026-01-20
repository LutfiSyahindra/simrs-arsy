<?php

namespace App\Http\Controllers\simrs\Pelayanan\display;

use App\Http\Controllers\Controller;
use App\Services\pelayanan\rajal\AntrianApotek3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApotekController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $AntrianApotekService;
    public function __construct(AntrianApotek3Service $AntrianApotekService)
    {
        $this->AntrianApotekService = $AntrianApotekService;
    }

    public function index()
    {
        return view('simrs.pelayanan.display.displayApotek');
    }

    public function panggilAntrean(){
        $data = $this->AntrianApotekService->getAntriapotek3();
        // Log::info($data);
        return response()->json($data);
    }

    public function updateAntrean(Request $request){
        $panggil = 'Dipanggil';
        $this->AntrianApotekService->updateAntriapotek3($panggil);
    }

    public function dataNonracikan(){
        $nonracikan = $this->AntrianApotekService->dataNonracikan();
        Log::info($nonracikan);
        return response()->json($nonracikan);
    }

    public function dataracikan(){
        $racikan = $this->AntrianApotekService->dataracikan();
        return response()->json($racikan);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
