<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Import\Keuangan\master\GapokImport;
use App\Services\masterData\masterGapokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class gapokController extends Controller
{

    protected $masterGapokService;
    public function __construct(masterGapokService $masterGapokService)
    {
        $this->masterGapokService = $masterGapokService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("simrs.masterData.Keuangan.gajiPokok.gapok");
    }

    public function getGapokTable()
    {
        $data = $this->masterGapokService->getGapokTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('gaji_pokok', function ($row) {
                return number_format($row['gaji_pokok'], 0, ',', '.');
            })
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editGapok('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteGapok('.$row['id'].')">
                        <i class="mdi mdi-trash-can"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function exportTemplate()
    {
        return $this->masterGapokService->exportTemplate();
    }

    public function importGapok(Request $request)
    {
        try {

            $request->validate([
                'file' => 'required|mimes:xls,xlsx|max:5120'
            ]);

            // reset counter di service
            $this->masterGapokService->resetCounter();

            Excel::import(
                new GapokImport($this->masterGapokService),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'added' => $this->masterGapokService->getAdded(),
                'skipped' => $this->masterGapokService->getSkipped()
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

            $validated = $request->validate([
                'nik' => 'required|string|max:50|unique:gaji_pokok,nik',
                'nama' => 'required|string|max:100',
                'jbtn' => 'required|string|max:100',
                'stts_kerja' => 'required|in:T,FT,PT',
                'masa_kerja' => 'required|in:FT>1,<1,PT',
                'gaji_pokok' => 'required|numeric|min:0',
            ], [
                'nik.required' => 'NIK wajib diisi',
                'nama.required' => 'Nama pegawai wajib diisi',
                'jbtn.required' => 'Jabatan wajib diisi',
                'stts_kerja.required' => 'Status kerja wajib dipilih',
                'stts_kerja.in' => 'Status kerja tidak valid',
                'masa_kerja.required' => 'Masa kerja wajib dipilih',
                'masa_kerja.in' => 'Masa kerja tidak valid',
                'gaji_pokok.required' => 'Gaji pokok wajib diisi',
                'gaji_pokok.numeric' => 'Gaji pokok harus berupa angka',
            ]);

            $gapok = $this->masterGapokService->create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Gaji Pokok berhasil disimpan',
                'data' => $gapok
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
        $dataGapok = $this->masterGapokService->findById($id);
        return response()->json($dataGapok);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {

            $validated = $request->validate([
                'nik' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('gaji_pokok', 'nik')->ignore($id)
                ],
                'nama' => 'required|string|max:100',
                'jbtn' => 'required|string|max:100',
                'stts_kerja' => 'required|in:T,FT,PT',
                'masa_kerja' => 'required|in:FT>1,<1,PT',
                'gaji_pokok' => 'required|numeric|min:0',
            ], [
                'nik.required' => 'NIK wajib diisi',
                'nik.unique' => 'NIK sudah digunakan',
                'nama.required' => 'Nama pegawai wajib diisi',
                'jbtn.required' => 'Jabatan wajib diisi',
                'stts_kerja.required' => 'Status kerja wajib dipilih',
                'stts_kerja.in' => 'Status kerja tidak valid',
                'masa_kerja.required' => 'Masa kerja wajib dipilih',
                'masa_kerja.in' => 'Masa kerja tidak valid',
                'gaji_pokok.required' => 'Gaji pokok wajib diisi',
                'gaji_pokok.numeric' => 'Gaji pokok harus berupa angka',
            ]);

            $gapok = $this->masterGapokService->update($id, $validated);

            return response()->json([
                'status' => true,
                'message' => 'Gaji Pokok berhasil diperbarui',
                'data' => $gapok
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
    public function destroy($id)
    {
        try {

            $this->masterGapokService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data gaji pokok berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
