<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Export\Keuangan\penggajian\GajiTahap2Export;
use App\Http\Controllers\Controller;
use App\Services\keuangan\penggajian\penggajianService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class penggajianController extends Controller
{
    protected $penggajianService;

    public function __construct(penggajianService $penggajianService)
    {
        $this->penggajianService = $penggajianService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('simrs.backOffice.keuangan.penggajian.penggajian');
    }

    public function getSummaryGajiTahap1(Request $request)
    {
        $periode = $request->input('periode');

        $summary = $this->penggajianService->getSummaryGajiTahap1($periode);

        return response()->json([
            'status' => true,
            'data' => $summary,
        ]);
    }

    public function getSummaryGajiTahap2(Request $request)
    {
        $periode = $request->input('periode');

        $summary = $this->penggajianService->getSummaryGajiTahap2($periode);

        return response()->json([
            'status' => true,
            'data' => $summary,
        ]);
    }

    public function getGajiTahap2GeneratorReadiness(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->penggajianService->getGajiTahap2GeneratorReadiness($validated['periode']),
        ]);
    }

    public function getGajiTahap1Table(Request $request)
    {
        $periode = $request->input('periode');

        $data = $this->penggajianService->getGajiTahap1Table($periode);

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('gapok', function ($row) {
                return 'Rp '.number_format($row['gapok'], 0, ',', '.');
            })
            ->editColumn('gaji_dibayarkan', function ($row) {
                return 'Rp '.number_format($row['gaji_dibayarkan'], 0, ',', '.');
            })
            ->editColumn('tunjangan', function ($row) {
                return 'Rp '.number_format($row['tunjangan'], 0, ',', '.');
            })
            ->editColumn('total', function ($row) {
                return 'Rp '.number_format($row['total'], 0, ',', '.');
            })
            ->addColumn('actions', function ($row) {
                $pdfUrl = route('backOffice.keuangan.penggajian.exportSlipGajiTahap1Pdf', $row['id']);
                $pdfButton = $row['can_export_slip'] ?? true
                    ? '
                        <a href="'.$pdfUrl.'"
                            target="_blank"
                            class="btn btn-outline-danger"
                            title="Export PDF">
                            <i class="mdi mdi-file-pdf-box"></i>
                        </a>
                    '
                    : '
                        <button type="button"
                            class="btn btn-outline-secondary"
                            disabled
                            title="'.e($row['slip_unavailable_message'] ?? 'Slip belum tersedia').'">
                            <i class="mdi mdi-file-pdf-box"></i>
                        </button>
                    ';

                return '
                    <div class="payroll-action-group">
                        <button class="btn btn-outline-primary"
                            title="Detail"
                            onclick="detailGajiTahap1('.$row['id'].')">
                            <i class="mdi mdi-eye"></i>
                        </button>

                        '.$pdfButton.'
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getGajiTahap2Table(Request $request)
    {
        $periode = $request->input('periode');

        $data = $this->penggajianService->getGajiTahap2Table($periode);

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('gaji_pokok', function ($row) {
                return 'Rp '.number_format($row['gaji_pokok'], 0, ',', '.');
            })
            ->editColumn('gaji_dibayarkan', function ($row) {
                return 'Rp '.number_format($row['gaji_dibayarkan'], 0, ',', '.');
            })
            ->editColumn('total_premi', function ($row) {
                return 'Rp '.number_format($row['total_premi'], 0, ',', '.');
            })
            ->editColumn('total_potongan', function ($row) {
                return 'Rp '.number_format($row['total_potongan'], 0, ',', '.');
            })
            ->editColumn('total', function ($row) {
                return 'Rp '.number_format($row['total'], 0, ',', '.');
            })
            ->addColumn('actions', function ($row) {
                $pdfUrl = route('backOffice.keuangan.penggajian.exportSlipGajiTahap2Pdf', $row['id']);
                $pdfButton = $row['can_export_slip'] ?? true
                    ? '
                        <a href="'.$pdfUrl.'"
                            target="_blank"
                            class="btn btn-outline-danger"
                            title="Lihat PDF Slip Gaji">
                            <i class="mdi mdi-file-pdf-box"></i>
                        </a>
                    '
                    : '
                        <button type="button"
                            class="btn btn-outline-secondary"
                            disabled
                            title="'.e($row['slip_unavailable_message'] ?? 'Slip belum tersedia').'">
                            <i class="mdi mdi-file-pdf-box"></i>
                        </button>
                    ';

                return '
                    <div class="payroll-action-group">
                        <button class="btn btn-outline-primary"
                            title="Detail"
                            onclick="detailGajiTahap2('.$row['id'].')">
                            <i class="mdi mdi-eye"></i>
                        </button>

                        '.$pdfButton.'
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function dokterUmumTahap2Options(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->penggajianService->dokterUmumTahap2Options($validated['q'] ?? null),
        ]);
    }

    public function gajiTahap2DoctorConfig()
    {
        return response()->json([
            'status' => true,
            'data' => $this->penggajianService->getGajiTahap2DoctorConfig(),
        ]);
    }

    public function updateGajiTahap2DoctorConfig(Request $request)
    {
        $premiumTypes = [
            'kebersamaan',
            'jasa_operasi',
            'jasa_rawat_jalan',
            'jasa_poli',
            'jasa_ecg',
            'konsul_wa',
            'jasa_igd',
            'kehadiran',
        ];

        $validated = $request->validate([
            'rows' => ['present', 'array'],
            'rows.*.kd_dokter' => ['required', 'string', 'max:30', 'distinct'],
            'rows.*.nm_dokter' => ['required', 'string', 'max:255'],
            'rows.*.kd_sps' => ['nullable', 'string', 'max:20'],
            'rows.*.nm_sps' => ['nullable', 'string', 'max:255'],
            'rows.*.include_salary' => ['required', 'boolean'],
            'rows.*.premium_types' => ['present', 'array'],
            'rows.*.premium_types.*' => ['required', 'string', Rule::in($premiumTypes)],
        ], [
            'rows.present' => 'Konfigurasi dokter tahap 2 wajib dikirim.',
            'rows.*.kd_dokter.distinct' => 'Dokter tahap 2 tidak boleh duplikat.',
            'rows.*.premium_types.*.in' => 'Pilihan sub generate premi dokter tidak valid.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi dokter umum tahap 2 berhasil disimpan.',
            'data' => $this->penggajianService->updateGajiTahap2DoctorConfig($validated['rows']),
        ]);
    }

    public function detailGajiTahap1($id)
    {
        $data = $this->penggajianService->detailGajiTahap1($id);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function detailGajiTahap2($id)
    {
        $data = $this->penggajianService->detailGajiTahap2($id);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function generateGajiTahap1(Request $request)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $result = $this->penggajianService->generateGajiTahap1($request->periode);

        return response()->json([
            'status' => true,
            'message' => 'Gaji tahap 1 berhasil digenerate',
            'data' => $result,
        ]);
    }

    public function generateGajiTahap2(Request $request)
    {
        $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $result = $this->penggajianService->generateGajiTahap2($request->periode);

        return response()->json([
            'status' => true,
            'message' => 'Gaji tahap 2 berhasil digenerate',
            'data' => $result,
        ]);
    }

    public function getPenerimaSlipWhatsappTahap1(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $data = $this->penggajianService->getPenerimaSlipWhatsappTahap1($validated['periode']);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function kirimSlipGajiWhatsappTahap1(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'gaji_ids' => ['required', 'array', 'min:1'],
            'gaji_ids.*' => ['required', 'integer'],
        ]);

        try {
            $result = $this->penggajianService->kirimSlipGajiWhatsappTahap1(
                $validated['periode'],
                $validated['gaji_ids']
            );

            return response()->json([
                'status' => true,
                'message' => 'Slip gaji berhasil dimasukkan ke antrean Whatsapp.',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Gagal mengirim slip gaji ke Whatsapp.',
            ], 500);
        }
    }

    public function exportSlipGajiTahap1Pdf($id)
    {
        $data = $this->penggajianService->detailSlipGajiTahap1($id);

        $pdf = Pdf::loadView('simrs.backOffice.keuangan.penggajian.slipGaji', [
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('slip-gaji-'.$data['nik'].'-'.$data['periode'].'.pdf');
    }

    public function exportSlipGajiTahap2Pdf($id)
    {
        $data = $this->penggajianService->detailSlipGajiTahap2($id);

        $pdf = Pdf::loadView('simrs.backOffice.keuangan.penggajian.slipGaji', [
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('slip-gaji-'.$data['nik'].'-'.$data['periode'].'.pdf');
    }

    public function exportGajiTahap2Excel(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
        ]);

        $periode = $validated['periode'];
        $payload = $this->penggajianService->getGajiTahap2ExportPayload($periode);

        return Excel::download(
            new GajiTahap2Export($payload),
            'gaji-tahap-2-'.$periode.'.xlsx'
        );
    }
}
