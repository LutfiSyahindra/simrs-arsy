<?php

namespace App\Http\Controllers\simrs\master\mapping;

use App\Http\Controllers\Controller;
use App\Import\Mapping\unitPegawaiImport;
use App\Services\mappingData\unitPegawaiService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class mappingUnitController extends Controller
{
    protected $unitPegawaiService;

    public function __construct(unitPegawaiService $unitPegawaiService)
    {
        $this->unitPegawaiService = $unitPegawaiService;
    }

    public function mappingUnit()
    {
        $guide = $this->unitPegawaiService->guideUnit()->map(function ($item) {
            return [
                'kode' => $item['kode'],
                'jenis' => $item['jenis'],
                'keterangan' => $item['keterangan'],
            ];
        });

        return view("simrs.masterData.mapping.mappingUnit.mappingUnit", compact('guide'));
    }

    public function unitTable()
    {
        $data = $this->unitPegawaiService->unitPegawaiTable();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('detail_control', function ($row) {
                return '
                    <button type="button" class="btn btn-light btn-sm btn-detail-unit" data-nik="'.e($row['nik']).'">
                        <i class="mdi mdi-chevron-down"></i>
                    </button>
                ';
            })
            ->rawColumns(['detail_control', 'status', 'total'])
            ->make(true);
    }

    public function getPegawai()
    {
        return response()->json($this->unitPegawaiService->getPegawai());
    }

    public function guideUnit()
    {
        return response()->json($this->unitPegawaiService->guideUnit());
    }

    public function generateKodeUnit()
    {
        return response()->json([
            'kode' => null
        ]);
    }

    public function exportTemplateUnit()
    {
        return $this->unitPegawaiService->exportTemplate();
    }

    public function importMappingUnit(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xls,xlsx|max:5120'
            ]);

            $this->unitPegawaiService->resetCounter();

            Excel::import(
                new unitPegawaiImport($this->unitPegawaiService),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'added' => $this->unitPegawaiService->getAdded(),
                'skipped' => $this->unitPegawaiService->getSkipped()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function storeUnit(Request $request)
    {
        if (is_array($request->input('nik'))) {
            return $this->storeUnitWithPegawaiSelection($request);
        }

        try {
            $request->validate([
                'nik' => 'required|string|exists:gaji_pokok,nik',
                'unit_id' => 'required|array|min:1',
                'unit_id.*' => 'nullable|exists:master_unit,id',
            ], [
                'nik.required' => 'Pegawai wajib dipilih',
                'nik.exists' => 'Pegawai tidak ditemukan',
                'unit_id.required' => 'Unit wajib dipilih',
            ]);

            $unitIds = collect($request->unit_id)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->toArray();

            if (empty($unitIds)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit wajib dipilih',
                    'errors' => [
                        'unit_id' => ['Unit wajib dipilih']
                    ]
                ], 422);
            }

            if (count($unitIds) !== count(array_unique($unitIds))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit tidak boleh duplikat',
                    'errors' => [
                        'unit_id' => ['Unit tidak boleh duplikat']
                    ]
                ], 422);
            }

            $existingIds = $this->unitPegawaiService->existingUnitIds($request->nik, $unitIds);

            if (!empty($existingIds)) {
                $existingNames = $this->unitPegawaiService->guideUnit()
                    ->whereIn('id', $existingIds)
                    ->map(fn ($unit) => $unit['kode'] . ' - ' . $unit['keterangan'])
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Unit sudah ada untuk pegawai ini: ' . $existingNames,
                    'errors' => [
                        'unit_id' => ['Unit sudah ada untuk pegawai ini: ' . $existingNames]
                    ]
                ], 422);
            }

            $this->unitPegawaiService->create($request->nik, $unitIds);

            return response()->json([
                'status' => true,
                'message' => 'Unit pegawai berhasil disimpan'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function storeUnitWithPegawaiSelection(Request $request)
    {
        try {
            $request->validate([
                'unit_id' => 'required|integer|exists:master_unit,id',
                'nik' => 'required|array|min:1',
                'nik.*' => 'required|string|exists:gaji_pokok,nik',
            ], [
                'unit_id.required' => 'Unit wajib dipilih',
                'unit_id.exists' => 'Unit tidak ditemukan',
                'nik.required' => 'Pegawai wajib dipilih',
                'nik.min' => 'Pegawai wajib dipilih',
                'nik.*.exists' => 'Pegawai tidak ditemukan',
            ]);

            $unitId = (int) $request->unit_id;
            $niks = collect($request->nik)
                ->filter()
                ->map(fn ($nik) => (string) $nik)
                ->values()
                ->toArray();

            if (empty($niks)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pegawai wajib dipilih',
                    'errors' => [
                        'nik' => ['Pegawai wajib dipilih']
                    ]
                ], 422);
            }

            if (count($niks) !== count(array_unique($niks))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pegawai tidak boleh duplikat',
                    'errors' => [
                        'nik' => ['Pegawai tidak boleh duplikat']
                    ]
                ], 422);
            }

            $existingNiks = $this->unitPegawaiService->existingNiksForUnit($unitId, $niks);

            if (!empty($existingNiks)) {
                $existingNames = $this->unitPegawaiService->getPegawai()
                    ->whereIn('nik', $existingNiks)
                    ->map(fn ($pegawai) => ($pegawai->nama ?? $pegawai->nik) . ' (' . $pegawai->nik . ')')
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Pegawai sudah memiliki unit ini: ' . $existingNames,
                    'errors' => [
                        'nik' => ['Pegawai sudah memiliki unit ini: ' . $existingNames]
                    ]
                ], 422);
            }

            $this->unitPegawaiService->createByUnit($unitId, $niks);

            return response()->json([
                'status' => true,
                'message' => 'Unit berhasil dipasang ke pegawai terpilih'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        return $this->storeUnit($request);
    }

    public function editUnit(string $id)
    {
        return response()->json($this->unitPegawaiService->findById($id));
    }

    public function edit(string $id)
    {
        return $this->editUnit($id);
    }

    public function getByPegawaiUnit($nik)
    {
        return response()->json($this->unitPegawaiService->getByPegawaiUnit($nik));
    }

    public function updateUnit(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'unit_id' => 'required|exists:master_unit,id',
            ], [
                'unit_id.required' => 'Unit wajib dipilih',
                'unit_id.exists' => 'Unit tidak ditemukan',
            ]);

            $row = $this->unitPegawaiService->findById($id);

            if (!$row) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data unit pegawai tidak ditemukan'
                ], 404);
            }

            $unitId = (int) $validated['unit_id'];

            if ($this->unitPegawaiService->existsUnitForPegawai($row->nik, $unitId, $row->id)) {
                $existingName = $this->unitPegawaiService->guideUnit()
                    ->where('id', $unitId)
                    ->map(fn ($unit) => $unit['kode'] . ' - ' . $unit['keterangan'])
                    ->first();

                return response()->json([
                    'status' => false,
                    'message' => 'Unit sudah ada untuk pegawai ini: ' . ($existingName ?? '-'),
                    'errors' => [
                        'unit_id' => ['Unit sudah ada untuk pegawai ini']
                    ]
                ], 422);
            }

            $result = $this->unitPegawaiService->update($id, $unitId);

            return response()->json([
                'status' => true,
                'message' => 'Unit pegawai berhasil diperbarui',
                'data' => $result
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        return $this->updateUnit($request, $id);
    }

    public function destroyUnit(string $id)
    {
        try {
            $this->unitPegawaiService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Unit pegawai berhasil dihapus'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        return $this->destroyUnit($id);
    }

    public function getGapokById($nik)
    {
        return response()->json($this->unitPegawaiService->getGapokById($nik));
    }
}
