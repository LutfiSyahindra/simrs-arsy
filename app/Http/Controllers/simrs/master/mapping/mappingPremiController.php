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

    public function getByPremi($id)
    {
        return response()->json($this->premiMappingService->getByPremi((int) $id));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'jnsPremi_id' => 'required|integer|exists:master_jenis_premi,id',
                'mappings' => 'required|array|min:1',
                'mappings.*.jnsTindakan_id' => 'required|integer|distinct|exists:master_jenis_tindakan,id',
                'mappings.*.persentase' => 'required|integer|min:0|max:100',
            ], [
                'jnsPremi_id.required' => 'Jenis premi wajib dipilih',
                'jnsPremi_id.exists' => 'Jenis premi tidak ditemukan',
                'mappings.required' => 'Jenis tindakan wajib ditambahkan',
                'mappings.min' => 'Jenis tindakan wajib ditambahkan',
                'mappings.*.jnsTindakan_id.required' => 'Jenis tindakan wajib dipilih',
                'mappings.*.jnsTindakan_id.distinct' => 'Jenis tindakan tidak boleh duplikat',
                'mappings.*.jnsTindakan_id.exists' => 'Jenis tindakan tidak ditemukan',
                'mappings.*.persentase.required' => 'Persentase wajib diisi',
                'mappings.*.persentase.integer' => 'Persentase harus berupa bilangan bulat',
                'mappings.*.persentase.min' => 'Persentase minimal 0%',
                'mappings.*.persentase.max' => 'Persentase maksimal 100%',
            ]);

            $premiId = (int) $validated['jnsPremi_id'];
            $tindakanIds = collect($validated['mappings'])
                ->pluck('jnsTindakan_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $existingIds = $this->premiMappingService->existingTindakanIds($premiId, $tindakanIds);

            if (!empty($existingIds)) {
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
                'persentase' => 'required|integer|min:0|max:100',
            ], [
                'jnsTindakan_id.required' => 'Jenis tindakan wajib dipilih',
                'jnsTindakan_id.exists' => 'Jenis tindakan tidak ditemukan',
                'persentase.required' => 'Persentase wajib diisi',
                'persentase.integer' => 'Persentase harus berupa bilangan bulat',
                'persentase.min' => 'Persentase minimal 0%',
                'persentase.max' => 'Persentase maksimal 100%',
            ]);

            $row = $this->premiMappingService->findById($id);

            if (!$row) {
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

            $this->premiMappingService->update($id, $tindakanId, (int) $validated['persentase']);

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
            if (!$this->premiMappingService->findById($id)) {
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
}
