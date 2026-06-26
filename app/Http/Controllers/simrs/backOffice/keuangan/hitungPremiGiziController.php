<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateGiziService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiGiziController extends Controller
{
    public function __construct(
        protected generateGiziService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateGizi.generateGizi');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_gizi' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_gizi'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $detail = '<button type="button" class="btn btn-outline-info btn-detail-gizi" data-id="'.$row['id'].'" title="Detail Data"><i class="mdi mdi-eye-outline me-1"></i>Detail</button>';

                if (! $row['is_locked']) {
                    return '
                        <div class="gizi-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-warning btn-lock-gizi" data-id="'.$row['id'].'" title="Kunci"><i class="mdi mdi-lock-outline"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-delete-gizi" data-id="'.$row['id'].'" title="Hapus"><i class="mdi mdi-delete-outline"></i></button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="gizi-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-success btn-unlock-gizi" data-id="'.$row['id'].'" title="Buka kunci"><i class="mdi mdi-lock-open-variant-outline"></i></button>
                        </div>
                    ';
                }

                return '
                    <div class="gizi-actions">
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
            'jenis_gizi' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_gizi']
            ),
        ]);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_gizi' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig($validated['jenis_gizi']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_gizi' => ['required', 'in:umum,bpjs'],
            'source_period_mode' => ['required', 'in:current,previous'],
            'konsul_jnsTindakan_ids' => ['nullable', 'array'],
            'konsul_jnsTindakan_ids.*' => ['integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'diit_jnsTindakan_ids' => ['nullable', 'array'],
            'diit_jnsTindakan_ids.*' => ['integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'konsul_pegawai_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'konsul_premi_bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'diit_petugas_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'diit_petugas_divider' => ['required', 'integer', 'min:1', 'max:999'],
            'diit_premi_bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'diit_premi_bersama_enabled' => ['required', 'boolean'],
            'recipients' => ['nullable', 'array'],
            'recipients.konsul_pegawai' => ['nullable', 'array'],
            'recipients.konsul_pegawai.*' => ['string', 'max:30'],
            'recipients.diit_petugas' => ['nullable', 'array'],
            'recipients.diit_petugas.*' => ['string', 'max:30'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi Gizi berhasil disimpan.',
            'data' => $this->service->updateConfig($validated['jenis_gizi'], $validated),
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
            'jenis_gizi' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview(
                $validated['periode'],
                $validated['jenis_gizi']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_gizi' => ['required', 'in:umum,bpjs'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_gizi']
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Gizi {$result['jenis_gizi_label']} berhasil digenerate.",
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
            'message' => 'Data Gizi berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data Gizi berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data Gizi berhasil dihapus.',
        ]);
    }
}
