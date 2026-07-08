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
            'jenis_premi_dokter' => ['nullable', Rule::in(['visite', 'kebersamaan'])],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
        ]);
        $jenisPremiDokter = $validated['jenis_premi_dokter'] ?? 'visite';
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_pelayanan'] ?? null,
                $jenisPremiDokter
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
            'kebersamaan_umum_percent' => $request->input('kebersamaan_umum_percent', 30),
            'kebersamaan_bpjs_nominal' => $request->input('kebersamaan_bpjs_nominal', 40000),
            'kebersamaan_bpjs_percent' => $request->input('kebersamaan_bpjs_percent', 30),
            'kebersamaan_divider' => $request->input('kebersamaan_divider', 4),
            'mapping_tindakan_ids' => $request->input('mapping_tindakan_ids', []),
            'doctor_configs' => $request->input('doctor_configs', []),
        ]);

        $validated = $request->validate([
            'visite_umum_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'visite_bpjs_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'visite_bpjs_nominal' => ['required', 'integer', 'min:0'],
            'source_period_mode' => ['required', Rule::in(['current', 'previous'])],
            'kebersamaan_umum_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'kebersamaan_bpjs_nominal' => ['required', 'integer', 'min:0'],
            'kebersamaan_bpjs_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'kebersamaan_divider' => ['required', 'integer', 'min:1', 'max:100'],
            'mapping_tindakan_ids' => ['present', 'array'],
            'mapping_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'doctor_configs' => ['present', 'array'],
            'doctor_configs.*.kategori' => [
                'required',
                Rule::in(['umum', 'spesialis_65', 'spesialis_80', 'kebersamaan']),
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
                (float) $validated['kebersamaan_umum_percent'],
                (int) $validated['kebersamaan_bpjs_nominal'],
                (float) $validated['kebersamaan_bpjs_percent'],
                (int) $validated['kebersamaan_divider'],
                $validated['mapping_tindakan_ids'],
                $validated['doctor_configs']
            ),
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_premi_dokter' => ['nullable', Rule::in(['visite', 'kebersamaan'])],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
        ]);
        $jenisPremiDokter = $validated['jenis_premi_dokter'] ?? 'visite';

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_pelayanan'] ?? null,
                $jenisPremiDokter
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_premi_dokter' => ['nullable', Rule::in(['visite', 'kebersamaan'])],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
        ]);
        $jenisPremiDokter = $validated['jenis_premi_dokter'] ?? 'visite';
        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_pelayanan'] ?? null,
            $jenisPremiDokter
        );

        return response()->json([
            'status' => true,
            'message' => "{$result['jenis_premi_dokter_label']} Dokter"
                .($jenisPremiDokter === 'visite' ? " {$result['jenis_pelayanan_label']}" : '')
                .' berhasil digenerate.',
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
