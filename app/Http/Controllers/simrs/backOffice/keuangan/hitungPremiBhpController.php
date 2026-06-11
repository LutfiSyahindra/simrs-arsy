<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateBhpService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiBhpController extends Controller
{
    public function __construct(
        protected generateBhpService $generateBhpService
    ) {
    }

    public function generateBhp()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateBhp.generateBhp');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_bhp' => ['nullable', 'in:umum,bpjs'],
        ]);

        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->generateBhpService->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_bhp'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $actions = '
                    <button type="button" class="btn btn-outline-primary btn-detail-bhp"
                        data-id="' . $row['id'] . '" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-bhp"
                            data-id="' . $row['id'] . '" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-bhp"
                            data-id="' . $row['id'] . '" title="Buka kunci">
                            <i class="mdi mdi-lock-open-variant-outline"></i>
                        </button>
                    ';
                } else {
                    $actions .= '
                        <button type="button" class="btn btn-light" disabled
                            title="Hanya Admin yang dapat membuka kunci">
                            <i class="mdi mdi-lock"></i>
                        </button>
                    ';
                }

                return '<div class="bhp-row-actions">' . $actions . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_bhp' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->generateBhpService->getSummary(
                $validated['periode'],
                $validated['jenis_bhp']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_bhp' => ['required', 'in:umum,bpjs'],
            'nominal_hitung' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ]);

        $result = $this->generateBhpService->generate(
            $validated['periode'],
            $validated['jenis_bhp'],
            (int) $validated['nominal_hitung']
        );

        return response()->json([
            'status' => true,
            'message' => "BHP {$result['jenis_bhp_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function detail(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => $this->generateBhpService->getDetail($id),
        ]);
    }

    public function lock(Request $request, int $id)
    {
        $result = $this->generateBhpService->lock($id, $request->user());

        return response()->json([
            'status' => true,
            'message' => 'Data BHP berhasil dikunci.',
            'data' => $result,
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        $result = $this->generateBhpService->unlock($id, $request->user());

        return response()->json([
            'status' => true,
            'message' => 'Kunci data BHP berhasil dibuka.',
            'data' => $result,
        ]);
    }
}
