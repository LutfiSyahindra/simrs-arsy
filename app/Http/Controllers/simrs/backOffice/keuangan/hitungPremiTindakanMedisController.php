<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateTindakanMedisService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiTindakanMedisController extends Controller
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
        protected generateTindakanMedisService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateTindakanMedis.generateTindakanMedis');
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
                    <button type="button" class="btn btn-outline-primary btn-detail-tindakan-medis"
                        data-id="'.$row['id'].'" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-tindakan-medis"
                            data-id="'.$row['id'].'" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-tindakan-medis"
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

                return '<div class="tm-actions">'.$actions.'</div>';
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
            'jnsTindakan_id' => $request->input('jnsTindakan_id', []),
            'doctor_codes' => $request->input('doctor_codes', []),
            'doctor_tindakan_ids' => $request->input('doctor_tindakan_ids', []),
        ]);

        $validated = $request->validate([
            'jnsPremi_id' => ['required', 'integer', 'exists:master_jenis_premi,id'],
            'bpjs_source_mode' => ['required', Rule::in(['current', 'previous'])],
            'distribution_mode' => ['required', Rule::in(['split_evenly', 'full_amount'])],
            'ignore_icu' => ['required', 'boolean'],
            'ignore_nicu' => ['required', 'boolean'],
            'bpjs_ignore_ugd' => ['required', 'boolean'],
            'bpjs_ignore_vk' => ['required', 'boolean'],
            'source_mappings' => ['present', 'array'],
            'source_mappings.*.source_pattern' => ['required', Rule::in(self::SOURCE_PATTERNS)],
            'source_mappings.*.jnsTindakan_id' => [
                'required',
                'integer',
                'exists:master_jenis_tindakan,id',
            ],
            'jnsTindakan_id' => ['present', 'array'],
            'jnsTindakan_id.*' => [
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
            'jnsPremi_id.required' => 'Sumber mapping premi wajib dipilih.',
            'jnsPremi_id.exists' => 'Sumber mapping premi tidak ditemukan.',
            'bpjs_source_mode.required' => 'Mode periode sumber BPJS wajib dipilih.',
            'bpjs_source_mode.in' => 'Mode periode sumber BPJS tidak valid.',
            'distribution_mode.required' => 'Mode distribusi nilai final wajib dipilih.',
            'distribution_mode.in' => 'Mode distribusi nilai final tidak valid.',
            'bpjs_ignore_ugd.required' => 'Konfigurasi BPJS untuk UGD wajib dikirim.',
            'bpjs_ignore_vk.required' => 'Konfigurasi BPJS untuk VK wajib dikirim.',
            'source_mappings.present' => 'Mapping sumber tindakan wajib dikirim.',
            'source_mappings.array' => 'Format mapping sumber tindakan tidak valid.',
            'source_mappings.*.source_pattern.required' => 'Sumber data wajib dipilih.',
            'source_mappings.*.source_pattern.in' => 'Sumber data tidak valid.',
            'source_mappings.*.jnsTindakan_id.required' => 'Tindakan mapping wajib dipilih.',
            'source_mappings.*.jnsTindakan_id.exists' => 'Tindakan mapping tidak ditemukan.',
            'jnsTindakan_id.present' => 'Daftar tindakan karcis wajib dikirim.',
            'jnsTindakan_id.array' => 'Format tindakan karcis tidak valid.',
            'jnsTindakan_id.*.exists' => 'Jenis tindakan karcis tidak ditemukan.',
            'jnsTindakan_id.*.distinct' => 'Jenis tindakan karcis tidak boleh duplikat.',
            'doctor_codes.present' => 'Daftar dokter wajib dikirim.',
            'doctor_codes.array' => 'Format daftar dokter tidak valid.',
            'doctor_codes.*.distinct' => 'Dokter tidak boleh duplikat.',
            'doctor_tindakan_ids.present' => 'Daftar tindakan filter dokter wajib dikirim.',
            'doctor_tindakan_ids.array' => 'Format tindakan filter dokter tidak valid.',
            'doctor_tindakan_ids.*.exists' => 'Tindakan filter dokter tidak ditemukan.',
            'doctor_tindakan_ids.*.distinct' => 'Tindakan filter dokter tidak boleh duplikat.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi tindakan medis berhasil disimpan.',
            'data' => $this->service->updateConfig(
                (int) $validated['jnsPremi_id'],
                $validated['bpjs_source_mode'],
                $validated['distribution_mode'],
                (bool) $validated['ignore_icu'],
                (bool) $validated['ignore_nicu'],
                (bool) $validated['bpjs_ignore_ugd'],
                (bool) $validated['bpjs_ignore_vk'],
                $validated['source_mappings'],
                $validated['jnsTindakan_id'],
                $validated['doctor_codes'],
                $validated['doctor_tindakan_ids']
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
            'data' => $this->service->dokterOptions($validated['q'] ?? null),
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
            'jnsPremi_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
            'ugd_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
            'vk_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_pelayanan'],
                isset($validated['jnsPremi_id']) ? (int) $validated['jnsPremi_id'] : null,
                isset($validated['ugd_plotingPremi_id'])
                    ? (int) $validated['ugd_plotingPremi_id']
                    : null,
                isset($validated['vk_plotingPremi_id'])
                    ? (int) $validated['vk_plotingPremi_id']
                    : null
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
            'jnsPremi_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
            'ugd_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
            'vk_plotingPremi_id' => ['nullable', 'integer', 'exists:master_ploting_premi,id'],
        ]);
        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_pelayanan'],
            isset($validated['jnsPremi_id']) ? (int) $validated['jnsPremi_id'] : null,
            isset($validated['ugd_plotingPremi_id'])
                ? (int) $validated['ugd_plotingPremi_id']
                : null,
            isset($validated['vk_plotingPremi_id'])
                ? (int) $validated['vk_plotingPremi_id']
                : null
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Tindakan Medis {$result['jenis_pelayanan_label']} berhasil digenerate.",
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
            'message' => 'Data tindakan medis berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci tindakan medis berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }
}
