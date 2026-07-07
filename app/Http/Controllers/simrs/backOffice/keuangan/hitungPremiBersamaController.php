<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generatePremiBersamaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiBersamaController extends Controller
{
    private const SOURCE_PATTERNS = [
        'rawat_*_pr',
        'rawat_jl_pr',
        'rawat_inap_pr',
        'rawat_*_dr',
        'rawat_jl_dr',
        'rawat_inap_dr',
        'rawat_*_drpr',
        'rawat_jl_drpr',
        'rawat_inap_drpr',
    ];

    public function __construct(
        protected generatePremiBersamaService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generatePremiBersama.generatePremiBersama');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
            'jnsPremi_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_pelayanan'] ?? null,
                isset($validated['jnsPremi_id']) ? (int) $validated['jnsPremi_id'] : null
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $actions = '
                    <button type="button" class="btn btn-outline-primary btn-detail-premi-bersama"
                        data-id="'.$row['id'].'" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-premi-bersama"
                            data-id="'.$row['id'].'" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-premi-bersama"
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

                return '<div class="pb-actions">'.$actions.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function mappingPremiOptions()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getMappingPremiOptions(),
        ]);
    }

    public function mappingActionOptions(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getActionOptions($id),
        ]);
    }

    public function plotingOptions()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getPlotingOptions(),
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
            'source_mappings' => $request->input('source_mappings', []),
            'included_umum_action_ids' => $request->input('included_umum_action_ids', []),
            'doctor_codes' => $request->input('doctor_codes', []),
            'doctor_tindakan_ids' => $request->input('doctor_tindakan_ids', []),
            'jnsPremi_bpjs_id' => $request->input(
                'jnsPremi_bpjs_id',
                $request->input('jnsPremi_umum_id')
            ),
            'bpjs_source_mode' => $request->input('bpjs_source_mode', 'previous'),
        ]);

        $validated = $request->validate([
            'jnsPremi_umum_id' => ['required', 'integer', 'exists:master_jenis_premi,id'],
            'jnsPremi_bpjs_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
            'bpjs_source_mode' => ['required', Rule::in(['current', 'previous'])],
            'ugd_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
            'vk_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
            'kamar_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
            'bhp_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
            'ignore_icu' => ['required', 'boolean'],
            'ignore_nicu' => ['required', 'boolean'],
            'source_mappings' => ['present', 'array'],
            'source_mappings.*.source_pattern' => ['required', Rule::in(self::SOURCE_PATTERNS)],
            'source_mappings.*.jnsTindakan_id' => [
                'required',
                'integer',
                'exists:master_jenis_tindakan,id',
            ],
            'included_umum_action_ids' => ['present', 'array'],
            'included_umum_action_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:master_jenis_tindakan,id',
            ],
            'doctor_codes' => ['present', 'array'],
            'doctor_codes.*' => ['required', 'string', 'max:30', 'distinct'],
            'doctor_tindakan_ids' => ['present', 'array'],
            'doctor_tindakan_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:master_jenis_tindakan,id',
            ],
        ], [
            'jnsPremi_umum_id.required' => 'Sumber mapping premi UMUM wajib dipilih.',
            'jnsPremi_umum_id.exists' => 'Sumber mapping premi UMUM tidak ditemukan.',
            'bpjs_source_mode.required' => 'Mode periode sumber BPJS wajib dipilih.',
            'bpjs_source_mode.in' => 'Mode periode sumber BPJS tidak valid.',
            'source_mappings.present' => 'Mapping sumber tindakan wajib dikirim.',
            'source_mappings.*.source_pattern.in' => 'Sumber data tidak valid.',
            'source_mappings.*.jnsTindakan_id.exists' => 'Tindakan mapping sumber tidak ditemukan.',
            'included_umum_action_ids.present' => 'Daftar tindakan UMUM wajib dikirim.',
            'included_umum_action_ids.*.exists' => 'Tindakan UMUM tidak ditemukan.',
            'doctor_codes.present' => 'Daftar dokter wajib dikirim.',
            'doctor_tindakan_ids.present' => 'Daftar tindakan filter dokter wajib dikirim.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi Premi Bersama berhasil disimpan.',
            'data' => $this->service->updateConfig(
                (int) $validated['jnsPremi_umum_id'],
                isset($validated['jnsPremi_bpjs_id']) ? (int) $validated['jnsPremi_bpjs_id'] : null,
                isset($validated['ugd_plotingPremi_id']) ? (int) $validated['ugd_plotingPremi_id'] : null,
                isset($validated['vk_plotingPremi_id']) ? (int) $validated['vk_plotingPremi_id'] : null,
                isset($validated['kamar_plotingPremi_id']) ? (int) $validated['kamar_plotingPremi_id'] : null,
                isset($validated['bhp_plotingPremi_id']) ? (int) $validated['bhp_plotingPremi_id'] : null,
                $validated['bpjs_source_mode'],
                (bool) $validated['ignore_icu'],
                (bool) $validated['ignore_nicu'],
                $validated['source_mappings'],
                $validated['included_umum_action_ids'],
                $validated['doctor_codes'],
                $validated['doctor_tindakan_ids']
            ),
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
            'jnsPremi_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_pelayanan'],
                isset($validated['jnsPremi_id']) ? (int) $validated['jnsPremi_id'] : null
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
            'jnsPremi_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
        ]);
        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_pelayanan'],
            isset($validated['jnsPremi_id']) ? (int) $validated['jnsPremi_id'] : null
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Bersama {$result['jenis_pelayanan_label']} berhasil digenerate.",
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
            'message' => 'Data Premi Bersama berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci Premi Bersama berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }
}
