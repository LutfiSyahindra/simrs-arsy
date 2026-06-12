<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateKamarService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiKamarController extends Controller
{
    public function __construct(
        protected generateKamarService $generateKamarService
    ) {}

    public function generateKamar()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateKamar.generateKamar');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_kamar' => ['nullable', 'in:umum,bpjs'],
        ]);

        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->generateKamarService->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_kamar'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $actions = '
                    <button type="button" class="btn btn-outline-primary btn-detail-kamar"
                        data-id="'.$row['id'].'" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-kamar"
                            data-id="'.$row['id'].'" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-kamar"
                            data-id="'.$row['id'].'" title="Buka kunci">
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

                return '<div class="bhp-row-actions">'.$actions.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_kamar' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->generateKamarService->getSummary(
                $validated['periode'],
                $validated['jenis_kamar']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_kamar' => ['required', 'in:umum,bpjs'],
            'nominal_hitung' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ]);

        $result = $this->generateKamarService->generate(
            $validated['periode'],
            $validated['jenis_kamar'],
            (int) $validated['nominal_hitung']
        );

        return response()->json([
            'status' => true,
            'message' => "Kamar {$result['jenis_kamar_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function detail(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => $this->generateKamarService->getDetail($id),
        ]);
    }

    public function lock(Request $request, int $id)
    {
        $result = $this->generateKamarService->lock($id, $request->user());

        return response()->json([
            'status' => true,
            'message' => 'Data kamar berhasil dikunci.',
            'data' => $result,
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        $result = $this->generateKamarService->unlock($id, $request->user());

        return response()->json([
            'status' => true,
            'message' => 'Kunci data kamar berhasil dibuka.',
            'data' => $result,
        ]);
    }
}
