<?php

namespace App\Http\Controllers\simrs\master\mapping;

use App\Http\Controllers\Controller;
use App\Services\mappingData\tindakanMappingService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class mappingTindakanController extends Controller
{
    protected $tindakanMappingService;

    public function __construct(tindakanMappingService $tindakanMappingService)
    {
        $this->tindakanMappingService = $tindakanMappingService;
    }

    public function mappingTindakan()
    {
        return view('simrs.masterData.mapping.mappingTindakan.mappingTindakan');
    }

    public function tindakanTable()
    {
        $data = $this->tindakanMappingService->tindakanTable();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('detail_control', function ($row) {
                return '
                    <button type="button" class="btn btn-light btn-sm btn-detail-tindakan" data-jenis-id="'.e($row['id']).'">
                        <i class="mdi mdi-chevron-down"></i>
                    </button>
                ';
            })
            ->rawColumns(['detail_control', 'total'])
            ->make(true);
    }

    public function guideJenisTindakan()
    {
        return response()->json($this->tindakanMappingService->guideJenisTindakan());
    }

    public function searchTindakan(Request $request)
    {
        $keyword = $request->input('q', $request->input('term', ''));

        return response()->json([
            'results' => $this->tindakanMappingService->searchTindakan($keyword),
            'pagination' => [
                'more' => false,
            ],
        ]);
    }

    public function getByJenisTindakan($id)
    {
        return response()->json($this->tindakanMappingService->getByJenisTindakan($id));
    }

    public function storeTindakan(Request $request)
    {
        try {
            $request->validate([
                'jnsTindakan_id' => 'required|integer|exists:master_jenis_tindakan,id',
                'source_keys' => 'required|array|min:1',
                'source_keys.*' => 'required|string|max:150',
            ], [
                'jnsTindakan_id.required' => 'Jenis tindakan wajib dipilih',
                'jnsTindakan_id.exists' => 'Jenis tindakan tidak ditemukan',
                'source_keys.required' => 'Tindakan wajib dipilih',
                'source_keys.min' => 'Tindakan wajib dipilih',
            ]);

            $sourceKeys = collect($request->source_keys)
                ->filter()
                ->map(fn ($sourceKey) => (string) $sourceKey)
                ->values()
                ->toArray();

            if (empty($sourceKeys)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tindakan wajib dipilih',
                    'errors' => [
                        'source_keys' => ['Tindakan wajib dipilih'],
                    ],
                ], 422);
            }

            if (count($sourceKeys) !== count(array_unique($sourceKeys))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tindakan tidak boleh duplikat',
                    'errors' => [
                        'source_keys' => ['Tindakan tidak boleh duplikat'],
                    ],
                ], 422);
            }

            $selected = $this->tindakanMappingService->findTindakanByKeys($sourceKeys);

            if ($selected->count() !== count($sourceKeys)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ada tindakan yang tidak ditemukan di data Khanza',
                    'errors' => [
                        'source_keys' => ['Ada tindakan yang tidak ditemukan di data Khanza'],
                    ],
                ], 422);
            }

            $existingKeys = $this->tindakanMappingService->existingSourceKeys((int) $request->jnsTindakan_id, $sourceKeys);

            if (!empty($existingKeys)) {
                $existingNames = collect($existingKeys)
                    ->map(fn ($sourceKey) => $selected->get($sourceKey)['nm_tindakan'] ?? $sourceKey)
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Tindakan sudah ada untuk jenis ini: ' . $existingNames,
                    'errors' => [
                        'source_keys' => ['Tindakan sudah ada untuk jenis ini: ' . $existingNames],
                    ],
                ], 422);
            }

            $this->tindakanMappingService->create((int) $request->jnsTindakan_id, $sourceKeys);

            return response()->json([
                'status' => true,
                'message' => 'Mapping tindakan berhasil disimpan',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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
        return $this->storeTindakan($request);
    }

    public function editTindakan(string $id)
    {
        return response()->json($this->tindakanMappingService->findById($id));
    }

    public function edit(string $id)
    {
        return $this->editTindakan($id);
    }

    public function updateTindakan(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'source_key' => 'required|string|max:150',
            ], [
                'source_key.required' => 'Tindakan wajib dipilih',
            ]);

            $row = $this->tindakanMappingService->findById($id);

            if (!$row) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data mapping tindakan tidak ditemukan',
                ], 404);
            }

            if ($validated['source_key'] === $row['source_key']) {
                return response()->json([
                    'status' => true,
                    'message' => 'Mapping tindakan tidak berubah',
                    'data' => $row,
                ]);
            }

            $selected = $this->tindakanMappingService->findTindakanByKeys([$validated['source_key']]);
            $sourceItem = $selected->get($validated['source_key']);

            if (!$sourceItem) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tindakan tidak ditemukan di data Khanza',
                    'errors' => [
                        'source_key' => ['Tindakan tidak ditemukan di data Khanza'],
                    ],
                ], 422);
            }

            if ($this->tindakanMappingService->existsMapping((int) $row['jnsTindakan_id'], $sourceItem, $id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tindakan sudah ada untuk jenis ini: ' . $sourceItem['nm_tindakan'],
                    'errors' => [
                        'source_key' => ['Tindakan sudah ada untuk jenis ini'],
                    ],
                ], 422);
            }

            $result = $this->tindakanMappingService->update($id, $sourceItem);

            return response()->json([
                'status' => true,
                'message' => 'Mapping tindakan berhasil diperbarui',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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
        return $this->updateTindakan($request, $id);
    }

    public function destroyTindakan(string $id)
    {
        try {
            $this->tindakanMappingService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Mapping tindakan berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        return $this->destroyTindakan($id);
    }
}
