<?php

namespace App\Http\Controllers\simrs\backOffice\keuangan;

use App\Http\Controllers\Controller;
use App\Services\keuangan\premi\generateApotekService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class hitungPremiApotekController extends Controller
{
    public function __construct(
        protected generateApotekService $service
    ) {}

    public function index()
    {
        return view('simrs.backOffice.keuangan.hitungPremi.generateApotek.generateApotek');
    }

    public function table(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'date_format:Y-m'],
            'jenis_apotek' => ['nullable', 'in:umum,bpjs'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
        ]);
        $canUnlock = $request->user()?->hasRole('Admin') ?? false;

        return DataTables::of(
            $this->service->getResults(
                $validated['periode'] ?? null,
                $validated['jenis_apotek'] ?? null,
                $validated['kategori_premi'] ?? 'apotek'
            )
        )
            ->addIndexColumn()
            ->addColumn('actions', function ($row) use ($canUnlock) {
                $detail = '<button type="button" class="btn btn-outline-info btn-detail-apotek" data-id="'.$row['id'].'" title="Detail Data"><i class="mdi mdi-eye-outline"></i></button>';

                if (! $row['is_locked']) {
                    return '
                        <div class="apotek-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-warning btn-lock-apotek" data-id="'.$row['id'].'" title="Kunci"><i class="mdi mdi-lock-outline"></i></button>
                            <button type="button" class="btn btn-outline-danger btn-delete-apotek" data-id="'.$row['id'].'" title="Hapus"><i class="mdi mdi-delete-outline"></i></button>
                        </div>
                    ';
                }

                if ($canUnlock) {
                    return '
                        <div class="apotek-actions">
                            '.$detail.'
                            <button type="button" class="btn btn-outline-success btn-unlock-apotek" data-id="'.$row['id'].'" title="Buka kunci"><i class="mdi mdi-lock-open-variant-outline"></i></button>
                        </div>
                    ';
                }

                return '
                    <div class="apotek-actions">
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
            'jenis_apotek' => ['required', 'in:umum,bpjs'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getSummary(
                $validated['periode'],
                $validated['jenis_apotek'],
                $validated['kategori_premi'] ?? 'apotek'
            ),
        ]);
    }

    public function config(Request $request)
    {
        $validated = $request->validate([
            'jenis_apotek' => ['required', 'in:umum,bpjs'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->getConfig(
                $validated['jenis_apotek'],
                $validated['kategori_premi'] ?? 'apotek'
            ),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'jenis_apotek' => ['required', 'in:umum,bpjs'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
            'jnsPremi_id' => ['nullable', 'integer', 'exists:master_jenis_premi,id'],
            'jnsTindakan_ids' => ['nullable', 'array'],
            'jnsTindakan_ids.*' => ['integer', 'distinct', 'exists:master_jenis_tindakan,id'],
            'tarif_per_item' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'source_period_mode' => ['nullable', 'in:previous,current'],
            'include_bpjs_in_umum' => ['nullable', 'boolean'],
            'jasa_farmasi_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula_31_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula_31_divider' => ['nullable', 'numeric', 'min:0.01', 'max:999'],
            'formula_7_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula_7_divider' => ['nullable', 'numeric', 'min:0.01', 'max:999'],
            'formula_12_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula_12_divider' => ['nullable', 'numeric', 'min:0.01', 'max:999'],
            'premi_bersama_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'recipients' => ['nullable', 'array'],
            'recipients.penerima_31' => ['nullable', 'array'],
            'recipients.penerima_31.*' => ['string', 'max:30'],
            'recipients.penerima_7' => ['nullable', 'array'],
            'recipients.penerima_7.*' => ['string', 'max:30'],
            'recipients.penerima_12' => ['nullable', 'array'],
            'recipients.penerima_12.*' => ['string', 'max:30'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi premi '.(($validated['kategori_premi'] ?? 'apotek') === 'apoteker' ? 'Apoteker' : 'Apotek').' berhasil disimpan.',
            'data' => $this->service->updateConfig(
                $validated['jenis_apotek'],
                $validated,
                $validated['kategori_premi'] ?? 'apotek'
            ),
        ]);
    }

    public function mappingOptions(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->mappingOptions(
                $validated['q'] ?? null,
                $validated['kategori_premi'] ?? 'apotek'
            ),
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
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_apotek' => ['required', 'in:umum,bpjs'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->service->preview(
                $validated['periode'],
                $validated['jenis_apotek'],
                $validated['kategori_premi'] ?? 'apotek'
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jenis_apotek' => ['required', 'in:umum,bpjs'],
            'kategori_premi' => ['nullable', 'in:apotek,apoteker'],
        ]);

        $result = $this->service->generate(
            $validated['periode'],
            $validated['jenis_apotek'],
            $validated['kategori_premi'] ?? 'apotek'
        );

        return response()->json([
            'status' => true,
            'message' => "{$result['kategori_premi_label']} {$result['jenis_apotek_label']} berhasil digenerate.",
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
            'message' => 'Data Apotek berhasil dikunci.',
            'data' => $this->service->lock($id, $request->user()),
        ]);
    }

    public function unlock(Request $request, int $id)
    {
        return response()->json([
            'status' => true,
            'message' => 'Kunci data Apotek berhasil dibuka.',
            'data' => $this->service->unlock($id, $request->user()),
        ]);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Data Apotek berhasil dihapus.',
        ]);
    }
}
