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
            'jenis_premi_dokter' => ['nullable', Rule::in(['visite', 'kebersamaan', 'jasa_operasi', 'jasa_rawat_jalan', 'jasa_poli', 'jasa_ecg', 'konsul_wa', 'jasa_igd', 'kehadiran'])],
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
            'kebersamaan_only_umum' => $request->boolean('kebersamaan_only_umum'),
            'ecg_nominal' => $request->input('ecg_nominal', 5000),
            'ecg_divider' => $request->input('ecg_divider', 3),
            'ecg_distribution_mode' => $request->input('ecg_distribution_mode', 'split_evenly'),
            'poli_percent' => $request->input('poli_percent', 30),
            'poli_distribution_mode' => $request->input('poli_distribution_mode', 'split_evenly'),
            'konsul_wa_nominal' => $request->input('konsul_wa_nominal', 0),
            'igd_nominal_per_pasien' => $request->input('igd_nominal_per_pasien', 30000),
            'kehadiran_nominal_per_hadir' => $request->input('kehadiran_nominal_per_hadir', 250000),
            'mapping_tindakan_ids' => $request->input('mapping_tindakan_ids', []),
            'ecg_mapping_tindakan_ids' => $request->input('ecg_mapping_tindakan_ids', []),
            'poli_mapping_tindakan_ids' => $request->input('poli_mapping_tindakan_ids', []),
            'poli_filter_doctor_codes' => $request->input('poli_filter_doctor_codes', []),
            'poli_filter_source_tables' => $request->input('poli_filter_source_tables', []),
            'poli_filter_tindakan_ids' => $request->input('poli_filter_tindakan_ids', []),
            'konsul_wa_mapping_tindakan_ids' => $request->input('konsul_wa_mapping_tindakan_ids', []),
            'doctor_configs' => $request->input('doctor_configs', []),
            'rawat_jalan_mapping_configs' => $request->input('rawat_jalan_mapping_configs', []),
            'rawat_jalan_special_doctors' => $request->input('rawat_jalan_special_doctors', []),
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
            'kebersamaan_only_umum' => ['required', 'boolean'],
            'ecg_nominal' => ['required', 'integer', 'min:0'],
            'ecg_divider' => ['required', 'integer', 'min:1', 'max:100'],
            'ecg_distribution_mode' => ['required', Rule::in(['split_evenly', 'full_amount'])],
            'poli_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'poli_distribution_mode' => ['required', Rule::in(['split_evenly', 'full_amount'])],
            'konsul_wa_nominal' => ['required', 'integer', 'min:0'],
            'igd_nominal_per_pasien' => ['required', 'integer', 'min:0'],
            'kehadiran_nominal_per_hadir' => ['required', 'integer', 'min:0'],
            'mapping_tindakan_ids' => ['present', 'array'],
            'mapping_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'ecg_mapping_tindakan_ids' => ['present', 'array'],
            'ecg_mapping_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'poli_mapping_tindakan_ids' => ['present', 'array'],
            'poli_mapping_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'poli_filter_doctor_codes' => ['present', 'array'],
            'poli_filter_doctor_codes.*' => ['required', 'string', 'max:30', 'distinct'],
            'poli_filter_source_tables' => ['present', 'array'],
            'poli_filter_source_tables.*' => ['required', 'string', 'distinct', Rule::in($this->service->poliSourceTableNames())],
            'poli_filter_tindakan_ids' => ['present', 'array'],
            'poli_filter_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'konsul_wa_mapping_tindakan_ids' => ['present', 'array'],
            'konsul_wa_mapping_tindakan_ids.*' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'doctor_configs' => ['present', 'array'],
            'doctor_configs.*.kategori' => [
                'required',
                Rule::in(['umum', 'spesialis_65', 'spesialis_80', 'kebersamaan', 'jasa_operasi', 'jasa_rawat_jalan', 'jasa_poli', 'jasa_ecg', 'konsul_wa', 'jasa_igd', 'kehadiran']),
            ],
            'doctor_configs.*.kd_dokter' => ['required', 'string', 'max:30'],
            'doctor_configs.*.percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'rawat_jalan_mapping_configs' => ['present', 'array'],
            'rawat_jalan_mapping_configs.*.jnsTindakan_id' => ['required', 'integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'rawat_jalan_mapping_configs.*.multiplier_type' => ['required', Rule::in(['nominal', 'percent'])],
            'rawat_jalan_mapping_configs.*.multiplier_value' => ['required', 'numeric', 'min:0'],
            'rawat_jalan_special_doctors' => ['present', 'array'],
            'rawat_jalan_special_doctors.*.group_key' => ['required', Rule::in(['rawat_jalan_khusus_45000', 'rawat_jalan_khusus_72000'])],
            'rawat_jalan_special_doctors.*.kd_dokter' => ['required', 'string', 'max:30'],
            'rawat_jalan_special_doctors.*.nominal' => ['required', 'integer', 'min:0'],
        ], [
            'mapping_tindakan_ids.present' => 'Master Mapping Tindakan untuk visite wajib dikirim.',
            'mapping_tindakan_ids.*.exists' => 'Master Mapping Tindakan untuk visite tidak ditemukan.',
            'ecg_mapping_tindakan_ids.present' => 'Master Mapping Tindakan ECG wajib dikirim.',
            'ecg_mapping_tindakan_ids.*.exists' => 'Master Mapping Tindakan ECG tidak ditemukan.',
            'poli_mapping_tindakan_ids.present' => 'Master Mapping Tindakan Poli wajib dikirim.',
            'poli_mapping_tindakan_ids.*.exists' => 'Master Mapping Tindakan Poli tidak ditemukan.',
            'poli_filter_doctor_codes.present' => 'Daftar dokter filter Poli wajib dikirim.',
            'poli_filter_doctor_codes.*.distinct' => 'Dokter filter Poli tidak boleh duplikat.',
            'poli_filter_source_tables.present' => 'Daftar sumber tabel rawat Poli wajib dikirim.',
            'poli_filter_source_tables.*.in' => 'Sumber tabel rawat Poli tidak valid.',
            'poli_filter_source_tables.*.distinct' => 'Sumber tabel rawat Poli tidak boleh duplikat.',
            'poli_filter_tindakan_ids.present' => 'Daftar tindakan filter Poli wajib dikirim.',
            'poli_filter_tindakan_ids.*.exists' => 'Tindakan filter Poli tidak ditemukan.',
            'poli_filter_tindakan_ids.*.distinct' => 'Tindakan filter Poli tidak boleh duplikat.',
            'konsul_wa_mapping_tindakan_ids.present' => 'Master Mapping Tindakan Konsul WA wajib dikirim.',
            'konsul_wa_mapping_tindakan_ids.*.exists' => 'Master Mapping Tindakan Konsul WA tidak ditemukan.',
            'rawat_jalan_mapping_configs.present' => 'Mapping tindakan Rawat Jalan wajib dikirim.',
            'rawat_jalan_mapping_configs.*.jnsTindakan_id.exists' => 'Master Mapping Tindakan Rawat Jalan tidak ditemukan.',
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
                (bool) $validated['kebersamaan_only_umum'],
                (int) $validated['ecg_nominal'],
                (int) $validated['ecg_divider'],
                $validated['ecg_distribution_mode'],
                (float) $validated['poli_percent'],
                $validated['poli_distribution_mode'],
                (int) $validated['konsul_wa_nominal'],
                (int) $validated['igd_nominal_per_pasien'],
                (int) $validated['kehadiran_nominal_per_hadir'],
                $validated['mapping_tindakan_ids'],
                $validated['doctor_configs'],
                $validated['ecg_mapping_tindakan_ids'],
                $validated['poli_mapping_tindakan_ids'],
                $validated['poli_filter_doctor_codes'],
                $validated['poli_filter_source_tables'],
                $validated['poli_filter_tindakan_ids'],
                $validated['konsul_wa_mapping_tindakan_ids'],
                $validated['rawat_jalan_mapping_configs'],
                $validated['rawat_jalan_special_doctors']
            ),
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_premi_dokter' => ['nullable', Rule::in(['visite', 'kebersamaan', 'jasa_operasi', 'jasa_rawat_jalan', 'jasa_poli', 'jasa_ecg', 'konsul_wa', 'jasa_igd', 'kehadiran'])],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
            'nominal_operasi' => ['nullable', 'integer', 'min:0'],
            'manual_doctor_rows' => ['nullable', 'array'],
            'manual_doctor_rows.*.kd_dokter' => ['required_with:manual_doctor_rows', 'string', 'max:30'],
            'manual_doctor_rows.*.jumlah' => ['required_with:manual_doctor_rows', 'integer', 'min:0'],
        ]);
        $jenisPremiDokter = $validated['jenis_premi_dokter'] ?? 'visite';

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_pelayanan'] ?? null,
                $jenisPremiDokter,
                $validated['nominal_operasi'] ?? null,
                $validated['manual_doctor_rows'] ?? []
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_premi_dokter' => ['nullable', Rule::in(['visite', 'kebersamaan', 'jasa_operasi', 'jasa_rawat_jalan', 'jasa_poli', 'jasa_ecg', 'konsul_wa', 'jasa_igd', 'kehadiran'])],
            'jenis_pelayanan' => ['nullable', 'in:umum,bpjs'],
            'nominal_operasi' => ['required_if:jenis_premi_dokter,jasa_operasi', 'nullable', 'integer', 'min:1'],
            'manual_doctor_rows' => ['required_if:jenis_premi_dokter,jasa_igd', 'required_if:jenis_premi_dokter,kehadiran', 'nullable', 'array'],
            'manual_doctor_rows.*.kd_dokter' => ['required_with:manual_doctor_rows', 'string', 'max:30'],
            'manual_doctor_rows.*.jumlah' => ['required_with:manual_doctor_rows', 'integer', 'min:0'],
        ]);
        $jenisPremiDokter = $validated['jenis_premi_dokter'] ?? 'visite';
        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_pelayanan'] ?? null,
            $jenisPremiDokter,
            $validated['nominal_operasi'] ?? null,
            $validated['manual_doctor_rows'] ?? []
        );

        return response()->json([
            'status' => true,
            'message' => "{$result['jenis_premi_dokter_label']} Dokter"
                .(in_array($jenisPremiDokter, ['visite', 'jasa_rawat_jalan', 'jasa_poli', 'jasa_ecg', 'konsul_wa'], true) ? " {$result['jenis_pelayanan_label']}" : '')
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
