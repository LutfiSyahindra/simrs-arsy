<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generatePremiDokterService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiDokterController extends Controller
{
    public function __construct(
        protected generatePremiDokterService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generatePremiDokter.generatePremiDokter');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_pelayanan'] ?? null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $actions = '
                    <button type="button" class="btn btn-outline-primary btn-detail-premi-dokter"
                        data-id="'.$row['id'].'" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-premi-dokter"
                            data-id="'.$row['id'].'" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-premi-dokter"
                            data-id="'.$row['id'].'" title="Buka kunci">
                            <i class="mdi mdi-lock-open-variant-outline"></i>
                        </button>
                    ';
                } else {
                    $actions .= '
                        <button type="button" class="btn btn-light" disabled>
                            <i class="mdi mdi-lock"></i>
                        </button>
                    ';
                }

                return '<div class="pd-actions">'.$actions.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function mappingTindakanOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->mappingTindakanOptions($validated['q'] ?? null),
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

    public function config()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig(),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $request->merge([
            'source_period_mode' => $request->input('source_period_mode', 'current'),
            'mapping_tindakan_ids' => $request->input('mapping_tindakan_ids', []),
            'doctor_configs' => $request->input('doctor_configs', []),
        ]);

        $validated = $request->validate([
            'visite_umum_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'visite_bpjs_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'visite_bpjs_nominal' => ['required', 'integer', 'min:0'],
            'source_period_mode' => ['required', Rule::in(['current', 'previous'])],
            'mapping_tindakan_ids' => ['present', 'array'],
            'mapping_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'doctor_configs' => ['present', 'array'],
            'doctor_configs.*.kategori' => [
                'required',
                Rule::in(['umum', 'spesialis_65', 'spesialis_80']),
            ],
            'doctor_configs.*.kd_dokter' => ['required', 'string', 'max:30'],
            'doctor_configs.*.percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'mapping_tindakan_ids.present' => 'Master Mapping Tindakan untuk visite wajib dikirim.',
            'mapping_tindakan_ids.*.exists' => 'Master Mapping Tindakan untuk visite tidak ditemukan.',
            'source_period_mode.in' => 'Mode periode sumber data tidak valid.',
            'doctor_configs.present' => 'Daftar dokter wajib dikirim.',
            'doctor_configs.*.kategori.in' => 'Kategori dokter tidak valid.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi dokter berhasil disimpan.',
            'data' => $this->service->updateConfig(
                (float) $validated['visite_umum_percent'],
                (float) $validated['visite_bpjs_percent'],
                (int) $validated['visite_bpjs_nominal'],
                $validated['source_period_mode'],
                $validated['mapping_tindakan_ids'],
                $validated['doctor_configs']
            ),
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_pelayanan']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
        ]);
        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_pelayanan']
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Dokter Visite {$result['jenis_pelayanan_label']} berhasil digenerate.",
            'data' => $result,
        ]);
    }

    public function detail(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getDetail($id),
        ]);
    }

    public function lock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Data premi dokter berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci premi dokter berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }
}
