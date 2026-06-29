<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generatePremiFisioService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiFisioController extends Controller
{
    public function __construct(
        protected generatePremiFisioService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateFisio.generateFisio');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_fisio' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_fisio'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $detail = '<button type="button" class="btn btn-outline-info btn-detail-fisio" data-id="'.$row['id'].'" title="Detail"><i class="mdi mdi-eye-outline"></i></button>';

                if (! $row['is_locked']) {
                    return '
                        <div class="fisio-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-warning btn-lock-fisio" data-id="'.$row['id'].'" title="Kunci"><i class="mdi mdi-lock-outline"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-delete-fisio" data-id="'.$row['id'].'" title="Hapus"><i class="mdi mdi-delete-outline"></i></button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="fisio-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-success btn-unlock-fisio" data-id="'.$row['id'].'" title="Buka kunci"><i class="mdi mdi-lock-open-variant-outline"></i></button>
                        </div>
                    ';
                }

                return '
                    <div class="fisio-actions">
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
            'jenis_fisio' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_fisio']
            ),
        ]);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_fisio' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig($validated['jenis_fisio']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_fisio' => ['required', 'in:umum,bpjs'],
            'grand_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'petugas1_mode' => ['required', 'in:percent,nominal'],
            'petugas1_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'petugas1_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'petugas2_mode' => ['required', 'in:percent,nominal'],
            'petugas2_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'petugas2_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'bersama_mode' => ['required', 'in:percent,nominal'],
            'bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bersama_nominal' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'petugas1_id' => ['nullable', 'string', 'max:30'],
            'petugas2_id' => ['nullable', 'string', 'max:30'],
            'tindakan' => ['nullable', 'array'],
            'tindakan.*.id' => ['nullable', 'integer'],
            'tindakan.*.kode_tindakan' => ['nullable', 'string', 'max:40'],
            'tindakan.*.nama_tindakan' => ['nullable', 'string', 'max:255'],
            'tindakan.*.harga' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'tindakan.*.is_active' => ['nullable', 'boolean'],
            'tindakan.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi fisio berhasil disimpan.',
            'data' => $this->service->updateConfig($validated['jenis_fisio'], $validated),
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

    public function tindakanOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->tindakanOptions($validated['q'] ?? null),
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate($this->generateRules());

        return response()->json([
            'status' => true,
            'data' => $this->service->preview($validated),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->generateRules());
        $result = $this->service->generate($validated);

        return response()->json([
            'status' => true,
            'message' => "Premi Fisio {$result['jenis_fisio_label']} berhasil digenerate.",
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
            'message' => 'Data fisio berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data fisio berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data fisio berhasil dihapus.',
        ]);
    }

    private function generateRules(): array
    {
        return [
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_fisio' => ['required', 'in:umum,bpjs'],
            'tindakan_id' => ['nullable', 'integer'],
            'jumlah_tindakan' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'tindakan_items' => ['nullable', 'array'],
            'tindakan_items.*.tindakan_id' => ['nullable', 'integer'],
            'tindakan_items.*.jumlah' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'jumlah_pasien' => ['nullable', 'integer', 'min:1', 'max:999999'],
        ];
    }
}
