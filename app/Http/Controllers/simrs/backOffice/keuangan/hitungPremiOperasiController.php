<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateOperasiService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiOperasiController extends Controller
{
    private const MAX_TOTAL_OPERASI = 999999999999;

    public function __construct(
        protected generateOperasiService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateOperasi.generateOperasi');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_operasi' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_operasi'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                if (! $row['is_locked']) {
                    return '
                        <div class="operasi-actions">
                            <button type="button" class="btn btn-outline-info btn-detail-operasi"
                                data-id="'.$row['id'].'" title="Detail data">
                                <i class="mdi mdi-eye-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-lock-operasi"
                                data-id="'.$row['id'].'" title="Kunci data">
                                <i class="mdi mdi-lock-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-operasi"
                                data-id="'.$row['id'].'" title="Hapus data">
                                <i class="mdi mdi-delete-outline"></i>
                            </button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="operasi-actions">
                            <button type="button" class="btn btn-outline-info btn-detail-operasi"
                                data-id="'.$row['id'].'" title="Detail data">
                                <i class="mdi mdi-eye-outline"></i>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-unlock-operasi"
                                data-id="'.$row['id'].'" title="Buka kunci">
                                <i class="mdi mdi-lock-open-variant-outline"></i>
                            </button>
                        </div>
                    ';
                }

                return '
                    <div class="operasi-actions">
                        <button type="button" class="btn btn-outline-info btn-detail-operasi"
                            data-id="'.$row['id'].'" title="Detail data">
                            <i class="mdi mdi-eye-outline"></i>
                        </button>
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
            'jenis_operasi' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_operasi']
            ),
        ]);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_operasi' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig($validated['jenis_operasi']),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_operasi' => ['required', 'in:umum,bpjs'],
            'default_nominal' => ['required_if:jenis_operasi,bpjs', 'nullable', 'integer', 'min:0', 'max:999999999999'],
            'instrumen_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'instrumen_premi_bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'instrumen_petugas_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'instrumen_petugas_kelompok_20_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'instrumen_petugas_kelompok_80_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'dokter_anastesi_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'perawat_anastesi_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'perawat_anastesi_petugas_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'perawat_anastesi_premi_bersama_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'recipients' => ['nullable', 'array'],
            'recipients.instrumen_20' => ['nullable', 'array'],
            'recipients.instrumen_20.*' => ['string', 'max:30'],
            'recipients.instrumen_80' => ['nullable', 'array'],
            'recipients.instrumen_80.*' => ['string', 'max:30'],
            'recipients.dokter_anastesi' => ['nullable', 'array'],
            'recipients.dokter_anastesi.*' => ['string', 'max:30'],
            'recipients.perawat_anastesi' => ['nullable', 'array'],
            'recipients.perawat_anastesi.*' => ['string', 'max:30'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi operasi berhasil disimpan.',
            'data' => $this->service->updateConfig($validated['jenis_operasi'], $validated),
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

    public function dokterOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->dokterOptions($validated['q'] ?? null),
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $this->validateGenerateInput($request);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview(
                $validated['jenis_operasi'],
                (int) $validated['total_operasi_resolved'],
                $validated['jumlah_pasien'] ?? null,
                $validated['nominal_pengali'] ?? null
            ),
        ]);
    }

    public function detail(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->detail($id),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateGenerateInput($request, true);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_operasi'],
            (int) $validated['total_operasi_resolved'],
            $validated['jumlah_pasien'] ?? null,
            $validated['nominal_pengali'] ?? null
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Operasi {$result['jenis_operasi_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function lock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Data operasi berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data operasi berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data operasi berhasil dihapus.',
        ]);
    }

    private function validateGenerateInput(Request $request, bool $withPeriod = false): array
    {
        $jenisOperasi = $request->input('jenis_operasi');
        $rules = [
            'jenis_operasi' => ['required', 'in:umum,bpjs'],
        ];

        if ($withPeriod) {
            $rules['periode'] = ['required', 'date_format:Y-m'];
        }

        if ($jenisOperasi === 'bpjs') {
            $rules['jumlah_pasien'] = ['required', 'integer', 'min:1', 'max:1000000'];
            $rules['nominal_pengali'] = ['required', 'integer', 'min:1', 'max:999999999999'];
            $rules['total_operasi'] = ['nullable', 'integer', 'min:0', 'max:'.self::MAX_TOTAL_OPERASI];
        } else {
            $rules['total_operasi'] = ['required', 'integer', 'min:0', 'max:'.self::MAX_TOTAL_OPERASI];
            $rules['jumlah_pasien'] = ['nullable', 'integer', 'min:0', 'max:1000000'];
            $rules['nominal_pengali'] = ['nullable', 'integer', 'min:0', 'max:999999999999'];
        }

        $validated = $request->validate($rules, [], [
            'jumlah_pasien' => 'jumlah PX BPJS',
            'nominal_pengali' => 'nominal BPJS',
            'total_operasi' => 'total nominal pendapatan operasi',
        ]);

        if (($validated['jenis_operasi'] ?? null) === 'bpjs') {
            $total = (int) $validated['jumlah_pasien'] * (int) $validated['nominal_pengali'];

            if ($total > self::MAX_TOTAL_OPERASI) {
                throw ValidationException::withMessages([
                    'nominal_pengali' => 'Grand total BPJS melebihi batas maksimal.',
                ]);
            }

            $validated['total_operasi_resolved'] = $total;

            return $validated;
        }

        $validated['total_operasi_resolved'] = (int) $validated['total_operasi'];

        return $validated;
    }
}
