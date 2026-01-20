<?php

namespace App\Http\Controllers\simrs\Pelayanan\petugasPanggil;

use App\Events\Pelayanan\PanggilAdmisiEvent;
use App\Http\Controllers\Controller;
use App\Services\pelayanan\rajal\AdmisiPanggilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class admisiPanggilController extends Controller
{

    protected $AdmisiPanggilService;
    public function __construct(AdmisiPanggilService $AdmisiPanggilService)
    {
        $this->AdmisiPanggilService = $AdmisiPanggilService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('simrs.pelayanan.petugasPanggil.admisi.admisiPanggil');
    }

    public function getDataPasien()
    {
        return $this->AdmisiPanggilService->getAllAdmisi();
    }

    public function panggilAdmisi(Request $request)
    {
        $nomorAntrian = $request->no_antrian;
        $loket = $request->loket;
        $id = $request->id;
        $status = $request->status; 
        Log::info([
            'nomorAntrian' => $nomorAntrian,
            'loket' => $loket,
            'id' => $id,
            'status' => $status
        ]);

        $this->AdmisiPanggilService->updateStatusPanggil($id);
        $this->AdmisiPanggilService->updateLoket($id, $loket);
        // Broadcast event ke Laravel Reverb
        event(new PanggilAdmisiEvent($nomorAntrian, $loket, $status));

        return response()->json([
            'status' => 'success',
            'message' => 'Pasien dipanggil!',
            'data' => compact('nomorAntrian', 'loket', 'id')
        ]);
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
