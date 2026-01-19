<?php

namespace App\Http\Controllers\simrs\Pelayanan\petugasPanggil;

use App\Events\Pelayanan\PanggilKasirEvent;
use App\Http\Controllers\Controller;
use App\Services\pelayanan\rajal\PoliService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class kasirPanggilController extends Controller
{

    protected $poliService;
    public function __construct(PoliService $poliService)
    {
        $this->poliService = $poliService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('simrs.pelayanan.petugasPanggil.kasir.kasirPanggil');
    }

    public function getDataPasien(Request $request)
    {
        Log::info($request);
        $jenisRawat = $request->jenis_rawat;
        $tgl1 = $request->start_date;
        $tgl2 = $request->end_date;

        if($jenisRawat == 'Rawat Jalan'){
            $dataPasien = $this->poliService->getDataPasien();
            return response()->json($dataPasien);
        }elseif ($jenisRawat == 'Rawat Inap') {
            $dataPasien = $this->poliService->getDataPasienRawatInap($tgl1, $tgl2);
            return response()->json($dataPasien);
        }
    }

    public function panggilKasir(Request $request)
    {
        $nama = $request->nama;
        $poli = $request->poli;
        $dokter = $request->dokter;
        $nomorAntrian = $request->no_reg;
        $noRawat = $request->noRawat;
        // Broadcast event ke Laravel Reverb
        event(new PanggilKasirEvent($nama, $poli, $dokter, $nomorAntrian, $noRawat));

        return response()->json([
            'message' => 'Pasien dipanggil!',
            'data' => compact('nama', 'poli', 'dokter', 'nomorAntrian')
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
