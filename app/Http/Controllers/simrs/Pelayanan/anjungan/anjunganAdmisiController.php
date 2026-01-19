<?php

namespace App\Http\Controllers\simrs\Pelayanan\anjungan;

use App\Events\Pelayanan\AdmisiAnjunganEvent;
use App\Http\Controllers\Controller;
use App\Services\pelayanan\rajal\admisiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class anjunganAdmisiController extends Controller
{

    protected $admisiService;
    public function __construct(admisiService $admisiService)
    {
        $this->admisiService = $admisiService;
    }
    /**
     * Display a listing of the resource.
     */
    public function generateAntrianAdmisi()
    {
        try {
            // Ambil model antrian penuh
            $antrian = $this->admisiService->generateNomorAntrian();

            // Log info dengan format array (aman, tidak kehilangan object)
            Log::info('Nomor Antrian Admisi baru dibuat', [
                'id' => $antrian->id,
                'no_antrian' => $antrian->no_antrian,
                'tanggal' => $antrian->tanggal,
                'status_panggil' => $antrian->status_panggil,
                'loket' => $antrian->loket,
            ]);

            // Kirim model ke event agar broadcastWith() bisa akses $this->antrian->id
            event(new AdmisiAnjunganEvent($antrian));

            // Return response JSON lengkap
            return response()->json([
                'status' => 'success',
                'nomor_antrian' => [
                    'id' => $antrian->id,
                    'no_antrian' => $antrian->no_antrian,
                    'tanggal' => $antrian->tanggal,
                    'status_panggil' => $antrian->status_panggil,
                    'loket' => $antrian->loket,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal membuat nomor antrian admisi', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil nomor antrian',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cetakAntrian($nomor)
    {
        $customPaper = array(0, 0, 226, 400); // 80mm x 150mm

        $pdf = Pdf::loadView('simrs.pelayanan.anjungan.admisi.pdfAntrianAnjunganAdmisi', ['nomor' => $nomor])
            ->setPaper($customPaper, 'portrait'); // Ukuran thermal printer

        return $pdf->stream("Antrian_$nomor.pdf");
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
