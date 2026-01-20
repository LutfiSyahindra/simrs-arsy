<?php

namespace App\Http\Controllers\simrs\Pelayanan\petugasPanggil\loket;

use App\Http\Controllers\Controller;
use App\Services\pelayanan\rajal\LoketAdmisiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoketAdmisiController extends Controller
{
    protected $LoketAdmisiService;
    public function __construct(LoketAdmisiService $LoketAdmisiService)
    {
        $this->LoketAdmisiService = $LoketAdmisiService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getAllLoket()
    {
        $lokets = $this->LoketAdmisiService->getAllLoket();
        Log::info($lokets);
        // Jika repository mengembalikan koleksi Eloquent, ubah jadi array JSON
        return response()->json($lokets);
    }

    public function lockLoket(Request $request)
    {
        $id = $request->id;
        $petugas = Auth::user()->name ?? 'Petugas';

        $result = $this->LoketAdmisiService->lockLoket($id, $petugas);

        if ($result['status'] === 'success') {
            // Simpan ID loket aktif di session
            session(['loket_aktif_id' => $id, 'loket_aktif_nama' => $result['data']->nama]);
        }

        return response()->json($result);
    }

    public function getLoketAktif()
    {
        $loketId = session('loket_aktif_id');
        $loketNama = session('loket_aktif_nama');

        if ($loketId && $loketNama) {
            return response()->json([
                'status' => 'success',
                'loket' => [
                    'id' => $loketId,
                    'nama' => $loketNama
                ]
            ]);
        }

        return response()->json(['status' => 'empty']);
    }

    public function unlockLoket(Request $request)
    {
        $id = $request->id;
        $result = $this->LoketAdmisiService->unlockLoket($id);
        session()->forget(['loket_aktif_id', 'loket_aktif_nama']);

        return response()->json($result);
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
