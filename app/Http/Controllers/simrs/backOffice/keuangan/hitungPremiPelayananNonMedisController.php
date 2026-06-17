<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generatePelayananNonMedisService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiPelayananNonMedisController extends Controller
{
    public function __construct(
        protected generatePelayananNonMedisService $service
    ) {}

    public function index()
    {
        return view(
            'simrs.backOffice.keuangan.hitungPremi.generatePelayananNonMedis.generatePelayananNonMedis'
        );
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
                    <button type="button" class="btn btn-outline-primary btn-detail-non-medis"
                        data-id="'.$row['id'].'" title="Lihat detail">
                        <i class="mdi mdi-eye-outline"></i>
                    </button>
                ';

                if (! $row['is_locked']) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-warning btn-lock-non-medis"
                            data-id="'.$row['id'].'" title="Kunci data">
                            <i class="mdi mdi-lock-outline"></i>
                        </button>
                    ';
                } elseif ($canUnlock) {
                    $actions .= '
                        <button type="button" class="btn btn-outline-success btn-unlock-non-medis"
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

                return '<div class="non-medis-actions">'.$actions.'</div>';
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

    public function karcisConfig()
    {
        return response()->json([
            'status' => true,
            'data' => $this->service->getKarcisConfig(),
        ]);
    }

    public function updateKarcisConfig(Request $request)
    {
        $request->merge([
            'jnsTindakan_id' => $request->input('jnsTindakan_id', []),
        ]);

        $validated = $request->validate([
            'jnsTindakan_id' => ['present', 'array'],
            'jnsTindakan_id.*' => [
                'required',
                'integer',
                'distinct',
                'exists:master_jenis_tindakan,id',
            ],
        ], [
            'jnsTindakan_id.present' => 'Daftar tindakan karcis wajib dikirim.',
            'jnsTindakan_id.array' => 'Format tindakan karcis tidak valid.',
            'jnsTindakan_id.*.exists' => 'Jenis tindakan tidak ditemukan.',
            'jnsTindakan_id.*.distinct' => 'Jenis tindakan tidak boleh duplikat.',
        ]);

        $this->service->saveKarcisConfig($validated['jnsTindakan_id']);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi karcis BPJS berhasil disimpan.',
            'data' => $this->service->getKarcisConfig(),
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
            'jnsPremi_id' => ['required', 'integer', 'exists:master_jenis_premi,id'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_pelayanan'],
                (int) $validated['jnsPremi_id']
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_pelayanan' => ['required', 'in:umum,bpjs'],
            'jnsPremi_id' => ['required', 'integer', 'exists:master_jenis_premi,id'],
        ]);
        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_pelayanan'],
            (int) $validated['jnsPremi_id']
        );

        return response()->json([
            'status' => true,
            'message' => "Premi Pelayanan Non Medis {$result['jenis_pelayanan_label']} berhasil digenerate.",
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
            'message' => 'Data pelayanan non medis berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci pelayanan non medis berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }
}
