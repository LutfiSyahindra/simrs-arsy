<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateNicuService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiNicuController extends Controller
{
    public function __construct(
        protected generateNicuService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateNicu.generateNicu');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_nicu' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_nicu'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $detail = '<button type="button" class="btn btn-outline-info btn-detail-icu" data-id="'.$row['id'].'" title="Detail Data"><i class="mdi mdi-eye-outline me-1"></i>Detail Data</button>';

                if (! $row['is_locked']) {
                    return '
                        <div class="icu-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-warning btn-lock-icu" data-id="'.$row['id'].'" title="Kunci"><i class="mdi mdi-lock-outline"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-delete-icu" data-id="'.$row['id'].'" title="Hapus"><i class="mdi mdi-delete-outline"></i></button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="icu-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-success btn-unlock-icu" data-id="'.$row['id'].'" title="Buka kunci"><i class="mdi mdi-lock-open-variant-outline"></i></button>
                        </div>
                    ';
                }

                return '
                    <div class="icu-actions">
                        '.$detail.'
                        <button type="button" class="btn btn-light" disabled title="Hanya Admin yang dapat membuka kunci"><i class="mdi mdi-lock"></i></button>
                    </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_nicu' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_nicu']
            ),
        ]);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_nicu' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig($validated['jenis_nicu']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_nicu' => ['required', 'in:umum,bpjs'],
            'jnsTindakan_ids' => ['nullable', 'array'],
            'jnsTindakan_ids.*' => ['integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'perawat_nicu_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pegawai_nicu_khusus_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'perawat_nicu_reguler_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'perawat_nicu_divider' => ['required', 'integer', 'min:1', 'max:999'],
            'premi_medis_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'premi_medis_divider' => ['required', 'integer', 'min:1', 'max:999999'],
            'premi_bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'critical_action_name' => ['nullable', 'string', 'max:255'],
            'critical_action_names' => ['nullable', 'array'],
            'critical_action_names.*' => ['string', 'max:255', 'distinct'],
            'recipients' => ['nullable', 'array'],
            'recipients.perawat_nicu' => ['nullable', 'array'],
            'recipients.perawat_nicu.*' => ['string', 'max:30'],
            'recipients.pegawai_nicu_khusus' => ['nullable', 'array'],
            'recipients.pegawai_nicu_khusus.*' => ['string', 'max:30'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi NICU berhasil disimpan.',
            'data' => $this->service->updateConfig($validated['jenis_nicu'], $validated),
        ]);
    }

    public function mappingOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->mappingOptions($validated['q'] ?? null),
        ]);
    }

    public function criticalActionOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->criticalActionOptions($validated['q'] ?? null),
        ]);
    }

    public function pegawaiOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->pegawaiOptions($validated['q'] ?? null),
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_nicu' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview(
                $validated['periode'],
                $validated['jenis_nicu']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_nicu' => ['required', 'in:umum,bpjs'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_nicu']
        );

        return response()->json([
            'status' => true,
            'message' => "Premi NICU {$result['jenis_nicu_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function detail(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->detail($id),
        ]);
    }

    public function lock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Data NICU berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data NICU berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data NICU berhasil dihapus.',
        ]);
    }
}



