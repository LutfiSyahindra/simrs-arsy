<?php

namespace App\Http\Controllers\simrs\master\mapping;

use App\Http\Controllers\Controller;
use App\Import\Mapping\skoringPegawaiImport;
use App\Services\mappingData\skorPegawaiService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class mappingSkorController extends Controller
{

    protected $skorPegawaiService;
    public function __construct(skorPegawaiService $skorPegawaiService)
    {
        $this->skorPegawaiService = $skorPegawaiService;
    }
    /**
     * Display a listing of the resource.
     */
    public function mappingSkor()
    {
        $guide = $this->skorPegawaiService->guideSkor()->map(function ($item) {
            return [
                'kd_skor' => $item['kd_skor'],
                'jenis' => $item['jenis'],
                'keterangan' => $item['keterangan'],
                'bobot_skor' => $item['bobot_skor'],
            ];
        });
        return view("simrs.masterData.mapping.mappingSkor.mappingSkor", compact('guide'));
    }

    public function skorTable()
    {
        $data = $this->skorPegawaiService->skorPegawaiTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('detail_control', function ($row) {
                return '
                    <button type="button" class="btn btn-light btn-sm btn-detail-skor" data-nik="'.e($row['nik']).'">
                        <i class="mdi mdi-chevron-down"></i>
                    </button>
                ';
            })
            ->rawColumns(['detail_control', 'status', 'total'])
            ->make(true);
    }

    public function getPegawai()
    {
        return response()->json($this->skorPegawaiService->getPegawai());
    }


    public function guideSkor()
    {
        return response()->json($this->skorPegawaiService->guideSkor());
    }

    public function generateKodeSkor()
    {
        return response()->json([
            'kode' => null
        ]);
    }

    public function exportTemplate()
    {
        return $this->skorPegawaiService->exportTemplate();
    }

    public function importMappingSkor(Request $request)
    {
        try {

            $request->validate([
                'file' => 'required|mimes:xls,xlsx|max:5120'
            ]);

            // reset counter di service
            $this->skorPegawaiService->resetCounter();

            Excel::import(
                new skoringPegawaiImport($this->skorPegawaiService),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'added' => $this->skorPegawaiService->getAdded(),
                'skipped' => $this->skorPegawaiService->getSkipped()
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nik' => 'required|string|exists:gaji_pokok,nik',
                'skor_id' => 'required|array|min:1',
                'skor_id.*' => 'nullable|exists:master_skor,id',
            ], [
                'nik.required' => 'Pegawai wajib dipilih',
                'nik.exists' => 'Pegawai tidak ditemukan',
                'skor_id.required' => 'Skor wajib dipilih',
            ]);

            $skorIds = collect($request->skor_id)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->toArray();

            if (empty($skorIds)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Skor wajib dipilih',
                    'errors' => [
                        'skor_id' => ['Skor wajib dipilih']
                    ]
                ], 422);
            }

            if (count($skorIds) !== count(array_unique($skorIds))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Skor tidak boleh duplikat',
                    'errors' => [
                        'skor_id' => ['Skor tidak boleh duplikat']
                    ]
                ], 422);
            }

            $existingIds = $this->skorPegawaiService->existingSkorIds($request->nik, $skorIds);

            if (!empty($existingIds)) {
                $existingNames = $this->skorPegawaiService->guideSkor()
                    ->whereIn('id', $existingIds)
                    ->pluck('keterangan')
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Skor sudah ada untuk pegawai ini: ' . $existingNames,
                    'errors' => [
                        'skor_id' => ['Skor sudah ada untuk pegawai ini: ' . $existingNames]
                    ]
                ], 422);
            }

            $this->skorPegawaiService->create($request->nik, $skorIds);

            return response()->json([
                'status' => true,
                'message' => 'Skoring pegawai berhasil disimpan'
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return response()->json($this->skorPegawaiService->getByPegawai($id));
    }

    public function getByPegawai($nik)
    {
        return response()->json($this->skorPegawaiService->getByPegawai($nik));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'skor_id' => 'required|exists:master_skor,id',
            ], [
                'skor_id.required' => 'Skor wajib dipilih',
                'skor_id.exists' => 'Skor tidak ditemukan',
            ]);

            $row = $this->skorPegawaiService->findById($id);

            if (!$row) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data skoring pegawai tidak ditemukan'
                ], 404);
            }

            $skorId = (int) $validated['skor_id'];

            if ($this->skorPegawaiService->existsSkorForPegawai($row->nik, $skorId, $row->id)) {
                $existingName = $this->skorPegawaiService->guideSkor()
                    ->where('id', $skorId)
                    ->pluck('keterangan')
                    ->first();

                return response()->json([
                    'status' => false,
                    'message' => 'Skor sudah ada untuk pegawai ini: ' . ($existingName ?? '-'),
                    'errors' => [
                        'skor_id' => ['Skor sudah ada untuk pegawai ini']
                    ]
                ], 422);
            }

            $result = $this->skorPegawaiService->update($id, $skorId);

            return response()->json([
                'status' => true,
                'message' => 'Skor pegawai berhasil diperbarui',
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->skorPegawaiService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Skor pegawai berhasil dihapus'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getGapokById($nik)
    {
        return response()->json($this->skorPegawaiService->getGapokById($nik));
    }
}
