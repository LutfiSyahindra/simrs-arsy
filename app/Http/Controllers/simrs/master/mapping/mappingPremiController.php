<?php

namespace App\Http\Controllers\simrs\master\mapping;

use App\Http\Controllers\Controller;
use App\Services\mappingData\premiMappingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class mappingPremiController extends Controller
{
    protected $premiMappingService;

    public function __construct(premiMappingService $premiMappingService)
    {
        $this->premiMappingService = $premiMappingService;
    }

    public function mappingPremi()
    {
        return view('simrs.masterData.mapping.mappingPremi.mappingPremi');
    }

    public function premiTable()
    {
        return DataTables::of($this->premiMappingService->premiTable())
            ->addIndexColumn()
            ->addColumn('detail_control', function ($row) {
                return '
                    <button type="button" class="btn btn-light btn-sm btn-detail-premi" data-premi-id="'.e($row['id']).'">
                        <i class="mdi mdi-chevron-down"></i>
                    </button>
                ';
            })
            ->rawColumns(['detail_control', 'total'])
            ->make(true);
    }

    public function guideJenisTindakan()
    {
        return response()->json($this->premiMappingService->guideJenisTindakan());
    }

    public function guideJenisPremi()
    {
        return response()->json($this->premiMappingService->guideJenisPremi());
    }

    public function guidePegawai()
    {
        return response()->json($this->premiMappingService->guidePegawai());
    }

    public function getByPremi($id)
    {
        return response()->json($this->premiMappingService->getByPremi((int) $id));
    }

    public function getPegawaiByPremi($id)
    {
        return response()->json($this->premiMappingService->getPegawaiByPremi((int) $id));
    }

    public function updatePegawai(Request $request, string $id)
    {
        try {
            if (! $this->premiMappingService->findPremiById((int) $id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Jenis premi tidak ditemukan',
                ], 404);
            }

            $validated = $request->validate([
                'nik' => 'present|array',
                'nik.*' => 'required|string|distinct|exists:gaji_pokok,nik',
            ], [
                'nik.present' => 'Daftar pegawai wajib dikirim',
                'nik.array' => 'Format pegawai tidak valid',
                'nik.*.distinct' => 'Pegawai tidak boleh duplikat',
                'nik.*.exists' => 'Pegawai tidak ditemukan',
            ]);

            $this->premiMappingService->syncPegawai((int) $id, $validated['nik']);

            return response()->json([
                'status' => true,
                'message' => 'Pegawai penerima premi berhasil diperbarui',
                'jumlah_pegawai' => count($validated['nik']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'jnsPremi_id' => 'required|integer|exists:master_jenis_premi,id',
                'mappings' => 'required|array|min:1',
                'mappings.*.jnsTindakan_id' => 'required|integer|distinct|exists:master_jenis_tindakan,id',
                'mappings.*.jenis' => 'required|in:persen,nominal',
                'mappings.*.nilai' => 'required|integer|min:0|max:2147483647',
            ], [
                'jnsPremi_id.required' => 'Jenis premi wajib dipilih',
                'jnsPremi_id.exists' => 'Jenis premi tidak ditemukan',
                'mappings.required' => 'Jenis tindakan wajib ditambahkan',
                'mappings.min' => 'Jenis tindakan wajib ditambahkan',
                'mappings.*.jnsTindakan_id.required' => 'Jenis tindakan wajib dipilih',
                'mappings.*.jnsTindakan_id.distinct' => 'Jenis tindakan tidak boleh duplikat',
                'mappings.*.jnsTindakan_id.exists' => 'Jenis tindakan tidak ditemukan',
                'mappings.*.jenis.required' => 'Jenis nilai wajib dipilih',
                'mappings.*.jenis.in' => 'Jenis nilai harus persen atau nominal',
                'mappings.*.nilai.required' => 'Nilai premi wajib diisi',
                'mappings.*.nilai.integer' => 'Nilai premi harus berupa bilangan bulat',
                'mappings.*.nilai.min' => 'Nilai premi minimal 0',
                'mappings.*.nilai.max' => 'Nilai nominal terlalu besar',
            ]);

            $this->validatePersenValues($validated['mappings']);

            $premiId = (int) $validated['jnsPremi_id'];
            $tindakanIds = collect($validated['mappings'])
                ->pluck('jnsTindakan_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $existingIds = $this->premiMappingService->existingTindakanIds($premiId, $tindakanIds);

            if (! empty($existingIds)) {
                $names = $this->premiMappingService->guideJenisTindakan()
                    ->whereIn('id', $existingIds)
                    ->pluck('jenis')
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Jenis tindakan sudah terpasang pada premi ini: '.$names,
                    'errors' => [
                        'mappings' => ['Jenis tindakan sudah terpasang pada premi ini: '.$names],
                    ],
                ], 422);
            }

            $this->premiMappingService->create($premiId, $validated['mappings']);

            return response()->json([
                'status' => true,
                'message' => 'Mapping premi berhasil disimpan',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'jnsTindakan_id' => 'required|integer|exists:master_jenis_tindakan,id',
                'jenis' => 'required|in:persen,nominal',
                'nilai' => 'required|integer|min:0|max:2147483647',
            ], [
                'jnsTindakan_id.required' => 'Jenis tindakan wajib dipilih',
                'jnsTindakan_id.exists' => 'Jenis tindakan tidak ditemukan',
                'jenis.required' => 'Jenis nilai wajib dipilih',
                'jenis.in' => 'Jenis nilai harus persen atau nominal',
                'nilai.required' => 'Nilai premi wajib diisi',
                'nilai.integer' => 'Nilai premi harus berupa bilangan bulat',
                'nilai.min' => 'Nilai premi minimal 0',
                'nilai.max' => 'Nilai nominal terlalu besar',
            ]);

            $this->validatePersenValues([$validated], false);

            $row = $this->premiMappingService->findById($id);

            if (! $row) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data mapping premi tidak ditemukan',
                ], 404);
            }

            $tindakanId = (int) $validated['jnsTindakan_id'];

            if ($this->premiMappingService->existsMapping($row['jnsPremi_id'], $tindakanId, $id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Jenis tindakan sudah terpasang pada premi ini',
                    'errors' => [
                        'jnsTindakan_id' => ['Jenis tindakan sudah terpasang pada premi ini'],
                    ],
                ], 422);
            }

            $this->premiMappingService->update(
                $id,
                $tindakanId,
                $validated['jenis'],
                (int) $validated['nilai']
            );

            return response()->json([
                'status' => true,
                'message' => 'Mapping premi berhasil diperbarui',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            if (! $this->premiMappingService->findById($id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data mapping premi tidak ditemukan',
                ], 404);
            }

            $this->premiMappingService->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Mapping premi berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function validatePersenValues(array $mappings, bool $nested = true): void
    {
        $errors = [];

        foreach ($mappings as $index => $mapping) {
            if (($mapping['jenis'] ?? null) === 'persen' && (int) ($mapping['nilai'] ?? 0) > 100) {
                $key = $nested ? "mappings.$index.nilai" : 'nilai';
                $errors[$key] = ['Nilai persen maksimal 100%'];
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
