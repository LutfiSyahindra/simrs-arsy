<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\premiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class premiController extends Controller
{
    protected $premiService;
    public function __construct(premiService $premiService)
    {
        $this->premiService = $premiService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('simrs.backOffice.keuangan.premi.premi');
    }

    public function getPremiTable(Request $request)
    {
        // ================= AMBIL FILTER =================
        $filter = [
            'status_bayar' => $request->status_bayar,   // piutang | lunas_non_piutang | belum_closing_kasir
            'penjamin'     => $request->penjamin,       // umum | bpjs | asuransi
            'status_rawat' => $request->status_rawat,   // rj | ri
            'jenis'        => $request->jenis ?? 'dokter', // 🔑 dokter | paramedis
        ];

        // ================= PANGGIL SERVICE =================
        $data = $this->premiService->getPremiTable(
            $request->tgl_awal,
            $request->tgl_akhir,
            $filter
        );

        return DataTables::of($data)
            ->addIndexColumn()

            // ===== KOLOM PREMI (FORMAT RUPIAH) =====
            ->editColumn('premi', function ($row) {
                return number_format($row['premi'], 0, ',', '.');
            })

            // ===== KOLOM ACTION =====
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-success"
                        onclick="detailPremi(
                            \'' . $row['kode'] . '\',
                            \'' . $row['jenis'] . '\',
                            \'' . $row['nama'] . '\'
                        )">
                        <i class="mdi mdi-eye"></i>
                    </button>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getPremiDetail(Request $request)
    {
        /* ================= NORMALISASI JENIS ================= */
        $jenis = strtolower($request->jenis);

        Log::info('Jenis Premi Detail', ['jenis' => $jenis]);

        if ($jenis === 'dokter') {
            $jenisFinal = 'Dokter';
            $kodeKey    = 'kd_dokter';
        } elseif (in_array($jenis, ['perawat', 'paramedis'])) {
            $jenisFinal = 'Perawat';
            $kodeKey    = 'nip';
        } else {
            return response()->json([
                'message' => 'Jenis premi tidak dikenali'
            ], 422);
        }

        /* ================= FILTER ================= */
        $filter = [
            'jenis'        => $jenisFinal,
            $kodeKey       => $request->kode,
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
            'sumber'       => $request->sumber,
            'layanan'      => $request->layanan, // 🔥 TAMBAHAN
        ];



        /* ================= SERVICE ================= */
        $data = $this->premiService->getPremiDetail(
            $request->tgl_awal,
            $request->tgl_akhir,
            $filter
        );

        $totalPremi = $this->premiService->getPremiDetailTotal(
            $request->tgl_awal,
            $request->tgl_akhir,
            $filter
        );

        /* ================= DATATABLES ================= */
        return DataTables::of($data)
            ->addIndexColumn()

            ->editColumn('tanggal', function ($r) {
                return date('d-m-Y', strtotime($r->tanggal));
            })

            ->addColumn('tindakan', fn ($r) => $r->tindakan)
            ->addColumn('layanan', fn ($r) => $r->layanan)
            ->addColumn('sumber', fn ($r) => $r->sumber)

            ->editColumn('nilai', function ($r) {
                return number_format($r->nilai, 0, ',', '.');
            })

            ->with([
                'total_premi' => $totalPremi // 🔥 INI KUNCINYA
            ])

            ->make(true);
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
