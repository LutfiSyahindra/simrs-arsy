<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateUgdService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiUgdController extends Controller
{
    public function __construct(
        protected generateUgdService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateUgd.generateUgd');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_ugd' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_ugd'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                if (! $row['is_locked']) {
                    return '
                        <div class="ugd-actions">
                            <button type="button" class="btn btn-outline-primary btn-edit-ugd"
                                data-id="'.$row['id'].'" title="Revisi data">
                                <i class="mdi mdi-pencil-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-lock-ugd"
                                data-id="'.$row['id'].'" title="Kunci data">
                                <i class="mdi mdi-lock-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-ugd"
                                data-id="'.$row['id'].'" title="Hapus data">
                                <i class="mdi mdi-delete-outline"></i>
                            </button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="ugd-actions">
                            <button type="button" class="btn btn-outline-success btn-unlock-ugd"
                                data-id="'.$row['id'].'" title="Buka kunci">
                                <i class="mdi mdi-lock-open-variant-outline"></i>
                            </button>
                        </div>
                    ';
                }

                return '
                    <div class="ugd-actions">
                        <button type="button" class="btn btn-light" disabled
                            title="Hanya Admin yang dapat membuka kunci">
                            <i class="mdi mdi-lock"></i>
                        </button>
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
            'jenis_ugd' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_ugd']
            ),
        ]);
    }

    public function dokterOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getDokterOptions($validated['q'] ?? null),
        ]);
    }

    public function plotingOptions()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getPlotingOptions(),
        ]);
    }

    public function copyPreview(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_ugd' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->copyPreview(
                $validated['periode'],
                $validated['jenis_ugd']
            ),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('entries')) {
            $validated = $request->validate([
                'periode' => ['required', 'date_format:Y-m'],
                'jenis_ugd' => ['required', 'in:umum,bpjs'],
                'entries' => ['required', 'array', 'min:1', 'max:300'],
                'entries.*.kd_dokter' => ['required', 'string', 'max:20'],
                'entries.*.plotingPremi_id' => ['required', 'integer', 'exists:master_ploting_premi,id'],
                'entries.*.jumlah_pasien' => ['required', 'integer', 'min:0', 'max:999999'],
                'entries.*.nominal_hitung' => ['required', 'integer', 'min:0', 'max:999999999999'],
            ]);

            $result = $this->service->generateMany(
                $validated['periode'],
                $validated['jenis_ugd'],
                $validated['entries']
            );

            return response()->json([
                'status' => true,
                'message' => "{$result['count']} data UGD {$result['jenis_ugd_label']} berhasil digenerate.",
                'data' => $result,
            ]);
        }

        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_ugd' => ['required', 'in:umum,bpjs'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'plotingPremi_id' => ['required', 'integer', 'exists:master_ploting_premi,id'],
            'jumlah_pasien' => ['required', 'integer', 'min:0', 'max:999999'],
            'nominal_hitung' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_ugd'],
            $validated['kd_dokter'],
            (int) $validated['plotingPremi_id'],
            (int) $validated['jumlah_pasien'],
            (int) $validated['nominal_hitung']
        );

        return response()->json([
            'status' => true,
            'message' => "UGD {$result['jenis_ugd_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function lock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Data UGD berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function lockAll(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_ugd' => ['required', 'in:umum,bpjs'],
        ]);
        $result = $this->service->lockAll(
            $validated['periode'],
            $validated['jenis_ugd'],
            $request->user()
        );

        return response()->json([
            'status' => true,
            'message' => "{$result['locked_count']} data UGD {$result['jenis_ugd_label']} berhasil dikunci.",
            'data' => $result,
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data UGD berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data UGD berhasil dihapus.',
        ]);
    }
}
