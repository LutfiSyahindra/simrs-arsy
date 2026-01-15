<?php

namespace App\Http\Controllers\simrs\Pelayanan\PetugasPanggil;

use App\Events\Pelayanan\PanggilPasienEvent;
use App\Http\Controllers\Controller;
use App\Services\pelayanan\rajal\PoliService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class poliPanggilController extends Controller
{
    protected $poliService;
    public function __construct(PoliService $poliService)
    {
        $this->poliService = $poliService;
    }
    /**
     * Display a listing of the resource.
     */
    public function poliPanggil()
    {
        return view('simrs.pelayanan.petugasPanggil.poli.poliPanggil');
    }

    public function getDataPoli()
    {
        $poli = $this->poliService->getPoliData();
        return response()->json($poli);
    }

    public function getDataDokter()
    {
        $kd_poli = request('PoliId'); // Pastikan parameter sesuai dengan yang dikirim dari AJAX
        $dokter = $this->poliService->getDokter($kd_poli);
        return response()->json($dokter);
    }

    public function getDataPasien()
    {
        $kd_poli = request('PoliId'); // Pastikan parameter sesuai dengan yang dikirim dari AJAX
        $kd_sps = request('DokterId'); // Pastikan parameter sesuai dengan yang dikirim dari AJAX
        if (!$kd_poli && !$kd_sps) {
            return response()->json(['error' => 'Poli ID is required'], 400);
        }

        $data = $this->poliService->getPasien($kd_poli, $kd_sps);
        return response()->json($data->toArray());
    }

    public function panggilPasien(Request $request)
    {
        $nama = $request->nama;
        $poli = $request->poli;
        $dokter = $request->dokter;
        $nomorAntrian = $request->no_reg;

        Log::info($nomorAntrian);

        // Broadcast event ke Laravel Reverb
        event(new PanggilPasienEvent($nama, $poli, $dokter, $nomorAntrian));

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
