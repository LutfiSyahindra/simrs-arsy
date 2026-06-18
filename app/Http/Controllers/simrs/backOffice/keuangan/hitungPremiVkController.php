<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateVkService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiVkController extends Controller
{
    public function __construct(
        protected generateVkService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateVk.generateVk');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_vk' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_vk'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                if (! $row['is_locked']) {
                    return '
                        <div class="vk-actions">
                            <button type="button" class="btn btn-outline-primary btn-edit-vk"
                                data-id="'.$row['id'].'" title="Revisi data">
                                <i class="mdi mdi-pencil-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-lock-vk"
                                data-id="'.$row['id'].'" title="Kunci data">
                                <i class="mdi mdi-lock-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-vk"
                                data-id="'.$row['id'].'" title="Hapus data">
                                <i class="mdi mdi-delete-outline"></i>
                            </button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="vk-actions">
                            <button type="button" class="btn btn-outline-success btn-unlock-vk"
                                data-id="'.$row['id'].'" title="Buka kunci">
                                <i class="mdi mdi-lock-open-variant-outline"></i>
                            </button>
                        </div>
                    ';
                }

                return '
                    <div class="vk-actions">
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
            'jenis_vk' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_vk']
            ),
        ]);
    }

    public function tindakanOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'results' => $this->service->getTindakanOptions($validated['q'] ?? null),
            'pagination' => [
                'more' => false,
            ],
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
            'jenis_vk' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->copyPreview(
                $validated['periode'],
                $validated['jenis_vk']
            ),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('entries')) {
            $validated = $request->validate([
                'periode' => ['required', 'date_format:Y-m'],
                'jenis_vk' => ['required', 'in:umum,bpjs'],
                'entries' => ['required', 'array', 'min:1', 'max:300'],
                'entries.*.nm_tindakan' => ['required', 'string', 'max:255'],
                'entries.*.plotingPremi_id' => ['required', 'integer', 'exists:master_ploting_premi,id'],
                'entries.*.jumlah_tindakan' => ['required', 'integer', 'min:0', 'max:999999'],
                'entries.*.nominal_hitung' => ['required', 'integer', 'min:0', 'max:999999999999'],
            ]);

            $result = $this->service->generateMany(
                $validated['periode'],
                $validated['jenis_vk'],
                $validated['entries']
            );

            return response()->json([
                'status' => true,
                'message' => "{$result['count']} data VK {$result['jenis_vk_label']} berhasil digenerate.",
                'data' => $result,
            ]);
        }

        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_vk' => ['required', 'in:umum,bpjs'],
            'nm_tindakan' => ['required', 'string', 'max:255'],
            'plotingPremi_id' => ['required', 'integer', 'exists:master_ploting_premi,id'],
            'jumlah_tindakan' => ['required', 'integer', 'min:0', 'max:999999'],
            'nominal_hitung' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_vk'],
            $validated['nm_tindakan'],
            (int) $validated['plotingPremi_id'],
            (int) $validated['jumlah_tindakan'],
            (int) $validated['nominal_hitung']
        );

        return response()->json([
            'status' => true,
            'message' => "VK {$result['jenis_vk_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function lock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Data VK berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function lockAll(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_vk' => ['required', 'in:umum,bpjs'],
        ]);
        $result = $this->service->lockAll(
            $validated['periode'],
            $validated['jenis_vk'],
            $request->user()
        );

        return response()->json([
            'status' => true,
            'message' => "{$result['locked_count']} data VK {$result['jenis_vk_label']} berhasil dikunci.",
            'data' => $result,
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data VK berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data VK berhasil dihapus.',
        ]);
    }
}
