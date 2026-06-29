<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generatePremiDriverService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiDriverController extends Controller
{
    public function __construct(
        protected generatePremiDriverService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generatePremiDriver.generatePremiDriver');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults($validated['periode'] ?? null)
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $actions = '
                    <button type="button" class="btn btn-outline-info btn-detail-driver"
                        data-id="'.$row['id'].'" title="Detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-primary btn-edit-driver"
                            data-id="'.$row['id'].'" title="Revisi">
                            <i class="mdi mdi-pencil-outline"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-lock-driver"
                            data-id="'.$row['id'].'" title="Kunci">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-delete-driver"
                            data-id="'.$row['id'].'" title="Hapus">
                            <i class="mdi mdi-delete-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-driver"
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

                return '<div class="driver-actions">'.$actions.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary($validated['periode'] ?? null),
        ]);
    }

    public function config()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig(),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'premi_bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi perhitungan driver ambulance berhasil disimpan.',
            'data' => $this->service->updateConfig((float) $validated['premi_bersama_percent']),
        ]);
    }

    public function tujuanList()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->tujuanList(),
        ]);
    }

    public function tujuanOptions()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->tujuanOptions(),
        ]);
    }

    public function storeTujuan(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'string', 'max:30'],
            'nama_tujuan' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tujuan ambulance berhasil disimpan.',
            'data' => $this->service->saveTujuan($validated),
        ]);
    }

    public function updateTujuan(Request $request, int $id)
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'string', 'max:30'],
            'nama_tujuan' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tujuan ambulance berhasil diperbarui.',
            'data' => $this->service->saveTujuan($validated, $id),
        ]);
    }

    public function deleteTujuan(int $id)
    {
        $this->service->deleteTujuan($id);

        return response()->json([
            'status' => true,
            'message' => 'Tujuan ambulance berhasil dihapus.',
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
            'pegawai_id' => ['required', 'string', 'max:30'],
            'entries' => ['required', 'array', 'min:1', 'max:100'],
            'entries.*.tujuan_id' => ['required', 'integer'],
            'entries.*.jumlah' => ['required', 'integer', 'min:1', 'max:999999'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview(
                $validated['pegawai_id'],
                $validated['entries']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'pegawai_id' => ['required', 'string', 'max:30'],
            'entries' => ['required', 'array', 'min:1', 'max:100'],
            'entries.*.tujuan_id' => ['required', 'integer'],
            'entries.*.jumlah' => ['required', 'integer', 'min:1', 'max:999999'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['pegawai_id'],
            $validated['entries']
        );

        return response()->json([
            'status' => true,
            'message' => "Premi driver ambulance {$result['pegawai_name']} periode {$result['periode']} berhasil digenerate.",
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
            'message' => 'Data premi driver berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data premi driver berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data premi driver berhasil dihapus.',
        ]);
    }
}
