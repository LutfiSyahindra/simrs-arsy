<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateLaboratoriumService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiLaboratoriumController extends Controller
{
    public function __construct(
        protected generateLaboratoriumService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateLaboratorium.generateLaboratorium');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_laboratorium' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_laboratorium'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $detail = '<button type="button" class="btn btn-outline-info btn-detail-laboratorium" data-id="'.$row['id'].'" title="Detail"><i class="mdi mdi-eye-outline"></i></button>';

                if (! $row['is_locked']) {
                    return '
                        <div class="laboratorium-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-warning btn-lock-laboratorium" data-id="'.$row['id'].'" title="Kunci"><i class="mdi mdi-lock-outline"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-delete-laboratorium" data-id="'.$row['id'].'" title="Hapus"><i class="mdi mdi-delete-outline"></i></button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="laboratorium-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-success btn-unlock-laboratorium" data-id="'.$row['id'].'" title="Buka kunci"><i class="mdi mdi-lock-open-variant-outline"></i></button>
                        </div>
                    ';
                }

                return '
                    <div class="laboratorium-actions">
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
            'jenis_laboratorium' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_laboratorium']
            ),
        ]);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_laboratorium' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig($validated['jenis_laboratorium']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_laboratorium' => ['required', 'in:umum,bpjs'],
            'petugas_mode' => ['required', 'in:percent,nominal,bersama_divider'],
            'petugas_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'petugas_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'bpjs_petugas_divider' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'bersama_mode' => ['required', 'in:source,percent,nominal'],
            'bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bersama_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'recipients' => ['nullable', 'array'],
            'recipients.*' => ['string', 'max:30'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi laboratorium berhasil disimpan.',
            'data' => $this->service->updateConfig($validated['jenis_laboratorium'], $validated),
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
            'jenis_laboratorium' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview(
                $validated['periode'],
                $validated['jenis_laboratorium']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_laboratorium' => ['required', 'in:umum,bpjs'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_laboratorium']
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Laboratorium {$result['jenis_laboratorium_label']} berhasil digenerate.",
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
            'message' => 'Data laboratorium berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data laboratorium berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data laboratorium berhasil dihapus.',
        ]);
    }
}
