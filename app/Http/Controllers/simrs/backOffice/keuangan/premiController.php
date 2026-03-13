<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Export\Keuangan\premi\PremiDetailExport;
use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\premiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
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

    private function buildPremiDetailData(Request $request)
    {
        /* ================= NORMALISASI JENIS ================= */
        $jenis = strtolower($request->jenis);

        if ($jenis === 'dokter') {
            $jenisFinal = 'Dokter';
            $kodeKey    = 'kd_dokter';
        } elseif (in_array($jenis, ['perawat', 'paramedis'])) {
            $jenisFinal = 'Perawat';
            $kodeKey    = 'nip';
        } elseif (in_array($jenis, ['kamar_inap', 'kamar'])) {
            $jenisFinal = 'Kamar';
            $kodeKey    = 'kd_kamar';
        }
            else {
            abort(422, 'Jenis premi tidak dikenali');
        }

        /* ================= FILTER ================= */
        $filter = [
            'jenis'        => $jenisFinal,
            $kodeKey       => $request->kode,
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
            'sumber'       => $request->sumber,
            'layanan'      => $request->layanan,
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

        return [
            'data'        => $data,
            'total_premi' => $totalPremi,
            'jenis'       => $jenisFinal,
            'kode'        => $request->kode,
            'tgl_awal'    => $request->tgl_awal,
            'tgl_akhir'   => $request->tgl_akhir,
            'penjamin'     => $request->penjamin,
        ];
    }

    public function getPremiTable(Request $request)
    {
        // ================= AMBIL FILTER =================
        $filter = [
            'status_bayar' => $request->status_bayar,   // piutang | lunas_non_piutang | belum_closing_kasir
            'penjamin'     => $request->penjamin,       // umum | bpjs | asuransi
            'status_rawat' => $request->status_rawat,   // rj | ri
            'jenis'        => $request->jenis ?? 'dokter', // 🔑 dokter | paramedis
            'view_mode'         => $request->view_mode, // detail | grouped
        ];

        // ================= PANGGIL SERVICE =================
        $data = $this->premiService->getPremiTable(
            $request->tgl_awal,
            $request->tgl_akhir,
            $filter
        );

        Log::info(['data'        => $data,
            'tgl_awal'    => $request->tgl_awal,
            'tgl_akhir'   => $request->tgl_akhir,
            'penjamin'    => $request->penjamin,
            'jenis'       => $request->jenis,
            'view_mode'   => $request->view_mode]);

        return DataTables::of($data)
            ->addIndexColumn()

            // ===== KOLOM PREMI (FORMAT RUPIAH) =====
            ->editColumn('premi', function ($row) {
                return number_format($row['premi'], 0, ',', '.');
            })

            // ===== KOLOM ACTION =====
            ->addColumn('actions', function ($row) {
                $mode = strtolower($row['jenis']) === 'kamar' ? 'kamar' : 'umum';
                return '
                <div class="btn-group btn-group-sm" role="group">

                    <button class="btn btn-outline-primary"
                        title="Lihat Detail"
                        onclick="detailPremi(
                            \'' . $row['kode'] . '\',
                            \'' . $row['jenis'] . '\',
                            \'' . e($row['nama']) . '\',
                            \'' . $mode . '\',
                        )">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Export PDF"
                        onclick="exportPremi(
                            \'' . $row['kode'] . '\',
                            \'' . $row['jenis'] . '\',
                            \'' . e($row['nama']) . '\',
                            \'pdf\'
                        )">
                        <i class="mdi mdi-file-pdf-box"></i>
                    </button>

                    <button class="btn btn-outline-success"
                        title="Export Excel"
                        onclick="exportPremiExcell(
                            \'' . $row['kode'] . '\',
                            \'' . $row['jenis'] . '\',
                            \'' . e($row['nama']) . '\',
                            \'excel\'
                        )">
                        <i class="mdi mdi-file-excel-box"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getPremiDetail(Request $request)
    {
        $result = $this->buildPremiDetailData($request);

        return DataTables::of($result['data'])
            ->addIndexColumn()

            ->editColumn('tanggal', fn ($r) =>
                date('d-m-Y', strtotime($r->tanggal))
            )

            ->editColumn('nilai', fn ($r) =>
                number_format($r->nilai, 0, ',', '.')
            )

            ->with([
                'total_premi' => $result['total_premi']
            ])
            ->make(true);
    }

    public function getPremiDokterChart(Request $request)
    {
        $tglAwal  = $request->tgl_awal;
        $tglAkhir = $request->tgl_akhir;

        $filter = [
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
        ];

        // DATA REAL PER HARI
        $data = $this->premiService
            ->getPremiDokterChart($tglAwal, $tglAkhir, $filter);

        return response()->json([
            // untuk x-axis
            'labels' => $data->pluck('tanggal'),

            // untuk line chart
            'series' => $data->pluck('total'),

            // angka besar
            'total'  => $data->sum('total'),
        ]);
    }

    public function getPremiDokterSummary(Request $request)
    {
        // tanggal dari date range picker
            $tglAwal  = Carbon::parse($request->tgl_awal);
            $tglAkhir = Carbon::parse($request->tgl_akhir);

            $filter = [
                'status_bayar' => $request->status_bayar,
                'status_rawat' => $request->status_rawat,
                'penjamin'     => $request->penjamin,
            ];

            // ================= PERIODE DIPILIH =================
            $awalPeriode = $tglAwal->copy()->startOfMonth()->toDateString();
            $akhirPeriode = $tglAwal->copy()->endOfMonth()->toDateString();

            // ================= BULAN SEBELUMNYA =================
            $awalBulanLalu = $tglAwal->copy()->subMonth()->startOfMonth()->toDateString();
            $akhirBulanLalu = $tglAwal->copy()->subMonth()->endOfMonth()->toDateString();

            // TOTAL PERIODE DIPILIH
            $totalPeriode = $this->premiService
                ->getPremiDokterChart($awalPeriode, $akhirPeriode, $filter)
                ->sum('total');

            // TOTAL BULAN SEBELUMNYA
            $totalBulanLalu = $this->premiService
                ->getPremiDokterChart($awalBulanLalu, $akhirBulanLalu, $filter)
                ->sum('total');

            // ================= SELISIH NOMINAL =================
            $selisih = $totalPeriode - $totalBulanLalu;

            // ================= PERSENTASE =================
            $persen = $totalBulanLalu > 0
                ? ($selisih / $totalBulanLalu) * 100
                : 0;

            return response()->json([
                'total_periode' => round($totalPeriode),
                'total_lalu'    => round($totalBulanLalu),
                'persen'        => round($persen, 2),
                'naik'          => $selisih > 0,
                'selisih'       => round($selisih),          // bisa + / -
                'selisih_abs'   => round(abs($selisih)),     // buat display
            ]);
    }

    public function getPremiDokterChartOverlay(Request $request)
    {
        $tglAwal  = Carbon::parse($request->tgl_awal);

        $filter = [
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
        ];

        // ================= BULAN INI =================
        $awalBulanIni  = $tglAwal->copy()->startOfMonth();
        $akhirBulanIni = $tglAwal->copy()->endOfMonth();

        // ================= BULAN LALU =================
        $awalBulanLalu  = $tglAwal->copy()->subMonth()->startOfMonth();
        $akhirBulanLalu = $tglAwal->copy()->subMonth()->endOfMonth();

        // data harian
        $thisMonth = $this->premiService
            ->getPremiDokterChart(
                $awalBulanIni->toDateString(),
                $akhirBulanIni->toDateString(),
                $filter
            )
            ->keyBy('tanggal');

        $lastMonth = $this->premiService
            ->getPremiDokterChart(
                $awalBulanLalu->toDateString(),
                $akhirBulanLalu->toDateString(),
                $filter
            )
            ->keyBy('tanggal');

        // ================= NORMALISASI TANGGAL =================
        $labels = [];
        $seriesThis = [];
        $seriesLast = [];

        $daysInMonth = $awalBulanIni->daysInMonth;

        for ($i = 1; $i <= $daysInMonth; $i++) {

            $tglThis = $awalBulanIni->copy()->day($i)->toDateString();
            $tglLast = $awalBulanLalu->copy()->day($i)->toDateString();

            $labels[] = $tglThis;

            $seriesThis[] = $thisMonth[$tglThis]->total ?? 0;
            $seriesLast[] = $lastMonth[$tglLast]->total ?? 0;
        }

        return response()->json([
            'labels'      => $labels,
            'this_month' => $seriesThis,
            'last_month' => $seriesLast,
        ]);
    }

    public function getPremiParamedisChart(Request $request)
    {
        $tglAwal  = $request->tgl_awal;
        $tglAkhir = $request->tgl_akhir;

        $filter = [
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
        ];

        // DATA REAL PER HARI
        $data = $this->premiService
            ->getPremiParamedisChart($tglAwal, $tglAkhir, $filter);

        return response()->json([
            // untuk x-axis
            'labels' => $data->pluck('tanggal'),

            // untuk line chart
            'series' => $data->pluck('total'),

            // angka besar
            'total'  => $data->sum('total'),
        ]);
    }

    public function getPremiParamedisSummary(Request $request)
    {
        // tanggal dari date range picker
            $tglAwal  = Carbon::parse($request->tgl_awal);
            $tglAkhir = Carbon::parse($request->tgl_akhir);

            $filter = [
                'status_bayar' => $request->status_bayar,
                'status_rawat' => $request->status_rawat,
                'penjamin'     => $request->penjamin,
            ];

            // ================= PERIODE DIPILIH =================
            $awalPeriode = $tglAwal->copy()->startOfMonth()->toDateString();
            $akhirPeriode = $tglAwal->copy()->endOfMonth()->toDateString();

            // ================= BULAN SEBELUMNYA =================
            $awalBulanLalu = $tglAwal->copy()->subMonth()->startOfMonth()->toDateString();
            $akhirBulanLalu = $tglAwal->copy()->subMonth()->endOfMonth()->toDateString();

            // TOTAL PERIODE DIPILIH
            $totalPeriode = $this->premiService
                ->getPremiParamedisChart($awalPeriode, $akhirPeriode, $filter)
                ->sum('total');

            // TOTAL BULAN SEBELUMNYA
            $totalBulanLalu = $this->premiService
                ->getPremiParamedisChart($awalBulanLalu, $akhirBulanLalu, $filter)
                ->sum('total');

            // ================= SELISIH NOMINAL =================
            $selisih = $totalPeriode - $totalBulanLalu;

            // ================= PERSENTASE =================
            $persen = $totalBulanLalu > 0
                ? ($selisih / $totalBulanLalu) * 100
                : 0;

            return response()->json([
                'total_periode' => round($totalPeriode),
                'total_lalu'    => round($totalBulanLalu),
                'persen'        => round($persen, 2),
                'naik'          => $selisih > 0,
                'selisih'       => round($selisih),          // bisa + / -
                'selisih_abs'   => round(abs($selisih)),     // buat display
            ]);
    }

    public function getPremiParamedisChartOverlay(Request $request)
    {
        $tglAwal  = Carbon::parse($request->tgl_awal);

        $filter = [
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
        ];

        // ================= BULAN INI =================
        $awalBulanIni  = $tglAwal->copy()->startOfMonth();
        $akhirBulanIni = $tglAwal->copy()->endOfMonth();

        // ================= BULAN LALU =================
        $awalBulanLalu  = $tglAwal->copy()->subMonth()->startOfMonth();
        $akhirBulanLalu = $tglAwal->copy()->subMonth()->endOfMonth();

        // data harian
        $thisMonth = $this->premiService
            ->getPremiParamedisChart(
                $awalBulanIni->toDateString(),
                $akhirBulanIni->toDateString(),
                $filter
            )
            ->keyBy('tanggal');

        $lastMonth = $this->premiService
            ->getPremiParamedisChart(
                $awalBulanLalu->toDateString(),
                $akhirBulanLalu->toDateString(),
                $filter
            )
            ->keyBy('tanggal');

        // ================= NORMALISASI TANGGAL =================
        $labels = [];
        $seriesThis = [];
        $seriesLast = [];

        $daysInMonth = $awalBulanIni->daysInMonth;

        for ($i = 1; $i <= $daysInMonth; $i++) {

            $tglThis = $awalBulanIni->copy()->day($i)->toDateString();
            $tglLast = $awalBulanLalu->copy()->day($i)->toDateString();

            $labels[] = $tglThis;

            $seriesThis[] = $thisMonth[$tglThis]->total ?? 0;
            $seriesLast[] = $lastMonth[$tglLast]->total ?? 0;
        }

        return response()->json([
            'labels'      => $labels,
            'this_month' => $seriesThis,
            'last_month' => $seriesLast,
        ]);
    }

    public function getPremiKamarChart(Request $request)
    {
        $tglAwal  = $request->tgl_awal;
        $tglAkhir = $request->tgl_akhir;

        $filter = [
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
        ];

        // DATA REAL PER HARI
        $data = $this->premiService
            ->getPremiKamarChart($tglAwal, $tglAkhir, $filter);

        return response()->json([
            // untuk x-axis
            'labels' => $data->pluck('tanggal'),

            // untuk line chart
            'series' => $data->pluck('total'),

            // angka besar
            'total'  => $data->sum('total'),
        ]);
    }

    public function getPremiKamarSummary(Request $request)
    {
        // tanggal dari date range picker
            $tglAwal  = Carbon::parse($request->tgl_awal);
            $tglAkhir = Carbon::parse($request->tgl_akhir);

            $filter = [
                'status_bayar' => $request->status_bayar,
                'status_rawat' => $request->status_rawat,
                'penjamin'     => $request->penjamin,
            ];

            // ================= PERIODE DIPILIH =================
            $awalPeriode = $tglAwal->copy()->startOfMonth()->toDateString();
            $akhirPeriode = $tglAwal->copy()->endOfMonth()->toDateString();

            // ================= BULAN SEBELUMNYA =================
            $awalBulanLalu = $tglAwal->copy()->subMonth()->startOfMonth()->toDateString();
            $akhirBulanLalu = $tglAwal->copy()->subMonth()->endOfMonth()->toDateString();

            // TOTAL PERIODE DIPILIH
            $totalPeriode = $this->premiService
                ->getPremiKamarChart($awalPeriode, $akhirPeriode, $filter)
                ->sum('total');

            // TOTAL BULAN SEBELUMNYA
            $totalBulanLalu = $this->premiService
                ->getPremiKamarChart($awalBulanLalu, $akhirBulanLalu, $filter)
                ->sum('total');

            // ================= SELISIH NOMINAL =================
            $selisih = $totalPeriode - $totalBulanLalu;

            // ================= PERSENTASE =================
            $persen = $totalBulanLalu > 0
                ? ($selisih / $totalBulanLalu) * 100
                : 0;

            return response()->json([
                'total_periode' => round($totalPeriode),
                'total_lalu'    => round($totalBulanLalu),
                'persen'        => round($persen, 2),
                'naik'          => $selisih > 0,
                'selisih'       => round($selisih),          // bisa + / -
                'selisih_abs'   => round(abs($selisih)),     // buat display
            ]);
    }

    public function getPremiKamarChartOverlay(Request $request)
    {
        $tglAwal  = Carbon::parse($request->tgl_awal);

        $filter = [
            'status_bayar' => $request->status_bayar,
            'status_rawat' => $request->status_rawat,
            'penjamin'     => $request->penjamin,
        ];

        // ================= BULAN INI =================
        $awalBulanIni  = $tglAwal->copy()->startOfMonth();
        $akhirBulanIni = $tglAwal->copy()->endOfMonth();

        // ================= BULAN LALU =================
        $awalBulanLalu  = $tglAwal->copy()->subMonth()->startOfMonth();
        $akhirBulanLalu = $tglAwal->copy()->subMonth()->endOfMonth();

        // data harian
        $thisMonth = $this->premiService
            ->getPremiKamarChart(
                $awalBulanIni->toDateString(),
                $akhirBulanIni->toDateString(),
                $filter
            )
            ->keyBy('tanggal');

        $lastMonth = $this->premiService
            ->getPremiKamarChart(
                $awalBulanLalu->toDateString(),
                $akhirBulanLalu->toDateString(),
                $filter
            )
            ->keyBy('tanggal');

        // ================= NORMALISASI TANGGAL =================
        $labels = [];
        $seriesThis = [];
        $seriesLast = [];

        $daysInMonth = $awalBulanIni->daysInMonth;

        for ($i = 1; $i <= $daysInMonth; $i++) {

            $tglThis = $awalBulanIni->copy()->day($i)->toDateString();
            $tglLast = $awalBulanLalu->copy()->day($i)->toDateString();

            $labels[] = $tglThis;

            $seriesThis[] = $thisMonth[$tglThis]->total ?? 0;
            $seriesLast[] = $lastMonth[$tglLast]->total ?? 0;
        }

        return response()->json([
            'labels'      => $labels,
            'this_month' => $seriesThis,
            'last_month' => $seriesLast,
        ]);
    }

    public function cetakPremiDetailPdf(Request $request)
    {
        $result = $this->buildPremiDetailData($request);

        // Ambil nama dokter / paramedis
        if ($result['jenis'] === 'Dokter') {

            $dokter = DB::connection('mysql_khanza')
                ->table('dokter')
                ->leftJoin('spesialis', 'dokter.kd_sps', '=', 'spesialis.kd_sps')
                ->where('dokter.kd_dokter', $request->kode)
                ->select(
                    'dokter.nm_dokter',
                    'spesialis.nm_sps'
                )
                ->first();

            $nama   = $dokter->nm_dokter ?? '-';
            $status = 'Dokter ' . ($dokter->nm_sps ?? '-');
            $JenisParamedis = 'Dokter';

        } elseif ($result['jenis'] === 'Kamar') {

            $kamar = DB::connection('mysql_khanza')
                ->table('kamar')
                ->where('kd_kamar', $request->kode)
                ->select('kd_kamar')
                ->first();

            $nama   = $kamar->kd_kamar ?? '-';
            $status = 'Kamar ' . ($kamar->kd_kamar ?? '-');
            $JenisParamedis = 'Kamar';

        } else {
            $paramedis = DB::connection('mysql_khanza')
                ->table('petugas')
                ->leftJoin('jabatan', 'petugas.kd_jbtn', '=', 'jabatan.kd_jbtn')
                ->where('nip', $request->kode)
                ->select(
                    'petugas.nama',
                    'jabatan.nm_jbtn'
                )
                ->first();

            $nama   = $paramedis->nama ?? '-';
            $status = $paramedis->nm_jbtn ?? '-';
            $JenisParamedis = 'Paramedis';
        }


        return Pdf::loadView('simrs.backOffice.keuangan.premi.cetak.premiPdf', [
            'data'        => $result['data'],
            'total'       => $result['total_premi'],
            'nama'        => $nama,
            'status'      => $status,
            'jenis'       => $result['jenis'],
            'JenisParamedis' => $JenisParamedis,
            'tgl_awal'    => $result['tgl_awal'],
            'tgl_akhir'   => $result['tgl_akhir'],
            'penjamin'     => $result['penjamin'],
        ])
        ->setPaper('A4', 'portrait')
        ->stream('Detail-Premi.pdf');
    }

    public function cetakPremiDetailExcel(Request $request)
    {
        $result = $this->buildPremiDetailData($request);

        if ($result['jenis'] === 'Dokter') {

            $dokter = DB::connection('mysql_khanza')
                ->table('dokter')
                ->leftJoin('spesialis', 'dokter.kd_sps', '=', 'spesialis.kd_sps')
                ->where('dokter.kd_dokter', $request->kode)
                ->select(
                    'dokter.nm_dokter',
                    'spesialis.nm_sps'
                )
                ->first();

            $result['nama']   = $dokter->nm_dokter ?? '-';
            $result['status'] = 'Dokter ' . ($dokter->nm_sps ?? '-');
            $result['jenis']  = 'Dokter';

        } elseif ($result['jenis'] === 'Kamar') {

            $kamar = DB::connection('mysql_khanza')
                ->table('kamar')
                ->where('kd_kamar', $request->kode)
                ->select('kd_kamar')
                ->first();

            $result['nama']   = $kamar->kd_kamar ?? '-';
            $status = 'Kamar ' . ($kamar->kd_kamar ?? '-');
            $JenisParamedis = 'Kamar';

        } else {

            $paramedis = DB::connection('mysql_khanza')
                ->table('petugas')
                ->leftJoin('jabatan', 'petugas.kd_jbtn', '=', 'jabatan.kd_jbtn')
                ->where('petugas.nip', $request->kode)
                ->select(
                    'petugas.nama',
                    'jabatan.nm_jbtn'
                )
                ->first();

            $result['nama']   = $paramedis->nama ?? '-';
            $result['status'] = $paramedis->nm_jbtn ?? '-';
            $result['jenis']  = 'Paramedis';
        }

        return Excel::download(
            new PremiDetailExport($result),
            'Detail-Premi.xlsx'
        );
    }

    public function cetakAllPremiPdf(Request $request)
    {
        // ================= AMBIL FILTER =================
        $filter = [
            'status_bayar' => $request->status_bayar,   // piutang | lunas_non_piutang | belum_closing_kasir
            'penjamin'     => $request->penjamin,       // umum | bpjs | asuransi
            'status_rawat' => $request->status_rawat,   // rj | ri
            'jenis'        => $request->jenis ?? 'dokter', // 🔑 dokter | paramedis
            'view_mode'    => $request->view_mode ?? 'grouped', // grouped | detail
        ];

        // ================= PANGGIL SERVICE =================
        $data = $this->premiService->getPremiTable(
            $request->tgl_awal,
            $request->tgl_akhir,
            $filter
        );

        Log::info(['data'        => $data,
            'tgl_awal'    => $request->tgl_awal,
            'tgl_akhir'   => $request->tgl_akhir,
            'penjamin'    => $request->penjamin,
            'jenis'       => $request->jenis,]);


        return Pdf::loadView('simrs.backOffice.keuangan.premi.cetak.premiAllPdf', [
            'data'        => $data,
            'tgl_awal'    => $request->tgl_awal,
            'tgl_akhir'   => $request->tgl_akhir,
            'penjamin'    => $request->penjamin,
            'jenis'       => $request->jenis,
            'view_mode'   => $request->view_mode
        ])
        ->setPaper('A4', 'portrait')
        ->stream('Detail-Premi.pdf');
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
