<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateCasemixService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiCasemixController extends Controller
{
    public function __construct(
        protected generateCasemixService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateCasemix.generateCasemix');
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
                $detail = '<button type="button" class="btn btn-outline-info btn-detail-casemix" data-id="'.$row['id'].'" title="Detail"><i class="mdi mdi-eye-outline"></i></button>';

                if (! $row['is_locked']) {
                    return '
                        <div class="casemix-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-warning btn-lock-casemix" data-id="'.$row['id'].'" title="Kunci"><i class="mdi mdi-lock-outline"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-delete-casemix" data-id="'.$row['id'].'" title="Hapus"><i class="mdi mdi-delete-outline"></i></button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="casemix-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-success btn-unlock-casemix" data-id="'.$row['id'].'" title="Buka kunci"><i class="mdi mdi-lock-open-variant-outline"></i></button>
                        </div>
                    ';
                }

                return '
                    <div class="casemix-actions">
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
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary($validated['periode']),
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
            'excellent_min_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'excellent_reward_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'good_min_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'good_reward_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'low_reward_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'team_pool_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'leader_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'kanit_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'inputer_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'questions' => ['nullable', 'array'],
            'questions.*' => ['nullable', 'array'],
            'questions.*.*.label' => ['nullable', 'string', 'max:120'],
            'questions.*.*.score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recipients' => ['nullable', 'array'],
            'recipients.leader' => ['nullable', 'array'],
            'recipients.leader.*' => ['string', 'max:30'],
            'recipients.kanit' => ['nullable', 'array'],
            'recipients.kanit.*' => ['string', 'max:30'],
            'recipients.inputer' => ['nullable', 'array'],
            'recipients.inputer.*' => ['string', 'max:30'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi Casemix berhasil disimpan.',
            'data' => $this->service->updateConfig($validated),
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
        $validated = $this->generateValidation($request);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview($validated),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->generateValidation($request);
        $result = $this->service->generate($validated);

        return response()->json([
            'status' => true,
            'message' => "Premi Casemix periode {$result['periode']} berhasil digenerate.",
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
            'message' => 'Data Casemix berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data Casemix berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data Casemix berhasil dihapus.',
        ]);
    }

    private function generateValidation(Request $request): array
    {
        return $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'biaya_rs' => ['required', 'integer', 'min:0', 'max:999999999999999'],
            'tarif_bpjs' => ['required', 'integer', 'min:0', 'max:999999999999999'],
            'verifikasi_hasil_bpjs' => ['required', 'integer', 'min:0', 'max:999999999999999'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'max:80'],
        ]);
    }
}
