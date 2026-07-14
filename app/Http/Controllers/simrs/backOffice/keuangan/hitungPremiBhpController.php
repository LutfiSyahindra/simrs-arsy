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
    ) {}

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
                        data-id="'.$row['id'].'" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-bhp"
                            data-id="'.$row['id'].'" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-delete-bhp"
                            data-id="'.$row['id'].'" title="Hapus data">
                            <i class="mdi mdi-delete-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-bhp"
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

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_bhp' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->generateBhpService->getConfig($validated['jenis_bhp']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_bhp' => ['required', 'in:umum,bpjs'],
            'nominal_defaults' => ['required', 'array', 'min:1'],
            'nominal_defaults.*.plotingPremi_id' => ['required', 'integer', 'distinct', 'exists:master_ploting_premi,id'],
            'nominal_defaults.*.default_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi nominal BHP berhasil disimpan.',
            'data' => $this->generateBhpService->updateConfig($validated['jenis_bhp'], $validated),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_bhp' => ['required', 'in:umum,bpjs'],
            'nominal_hitung' => ['required', 'array'],
            'nominal_hitung.*' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ]);

        $result = $this->generateBhpService->generate(
            $validated['periode'],
            $validated['jenis_bhp'],
            $validated['nominal_hitung']
        );

        return response()->json([
            'status' => true,
            'message' => "BHP {$result['jenis_bhp_label']} berhasil digenerate untuk {$result['generated_count']} ploting.",
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

    public function lockAll(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_bhp' => ['required', 'in:umum,bpjs'],
        ]);

        $result = $this->generateBhpService->lockAll(
            $validated['periode'],
            $validated['jenis_bhp'],
            $request->user()
        );

        return response()->json([
            'status' => true,
            'message' => "{$result['locked_count']} data BHP {$result['jenis_bhp_label']} berhasil dikunci.",
            'data' => $result,
        ]);
    }

    public function destroy(int $id)
    {
        $this->generateBhpService->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data BHP berhasil dihapus.',
        ]);
    }
}
