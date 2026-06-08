<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\penggajian\penggajianService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        return view("simrs.backOffice.keuangan.penggajian.penggajian");
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

    public function getGajiTahap1Table(Request $request)
    {
        $periode = $request->input('periode');

        $data = $this->penggajianService->getGajiTahap1Table($periode);

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('gapok', function ($row) {
                return 'Rp ' . number_format($row['gapok'], 0, ',', '.');
            })
            ->editColumn('gaji_dibayarkan', function ($row) {
                return 'Rp ' . number_format($row['gaji_dibayarkan'], 0, ',', '.');
            })
            ->editColumn('tunjangan', function ($row) {
                return 'Rp ' . number_format($row['tunjangan'], 0, ',', '.');
            })
            ->editColumn('total', function ($row) {
                return 'Rp ' . number_format($row['total'], 0, ',', '.');
            })
            ->addColumn('actions', function ($row) {
                $pdfUrl = route('backOffice.keuangan.penggajian.exportSlipGajiTahap1Pdf', $row['id']);

                return '
                    <div class="payroll-action-group">
                        <button class="btn btn-outline-primary"
                            title="Detail"
                            onclick="detailGajiTahap1(' . $row['id'] . ')">
                            <i class="mdi mdi-eye"></i>
                        </button>

                        <a href="' . $pdfUrl . '"
                            target="_blank"
                            class="btn btn-outline-danger"
                            title="Export PDF">
                            <i class="mdi mdi-file-pdf-box"></i>
                        </a>
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
    
    public function detailGajiTahap1($id)
    {
        $data = $this->penggajianService->detailGajiTahap1($id);

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
        $data = $this->penggajianService->detailGajiTahap1($id);

        $pdf = Pdf::loadView('simrs.backOffice.keuangan.penggajian.slipGaji', [
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('slip-gaji-' . $data['nik'] . '-' . $data['periode'] . '.pdf');
    }
}
