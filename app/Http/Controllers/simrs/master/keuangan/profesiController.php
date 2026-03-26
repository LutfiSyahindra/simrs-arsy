<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Import\Keuangan\master\GapokImport;
use App\Services\masterData\jabatanService;
use App\Services\masterData\profesiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class profesiController extends Controller
{

    protected $profesiService;
    public function __construct(profesiService $profesiService)
    {
        $this->profesiService = $profesiService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("simrs.masterData.Keuangan.profesi.profesi");
    }

    public function profesiTable()
    {
        $data = $this->profesiService->profesiTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('tunjangan', function ($row) {
                return number_format($row['tunjangan'], 0, ',', '.');
            })
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editProfesi('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteProfesi('.$row['id'].')">
                        <i class="mdi mdi-trash-can"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function generateKodeProfesi()
    {
        return response()->json($this->profesiService->generateKodeProfesi());
    }

    public function store(Request $request)
    {
        try {

            // ✅ VALIDASI ARRAY
            $validated = $request->validate([
                'kode' => 'required|array|min:1',
                'kode.*' => 'required|string|max:20',
                'nama' => 'required|array|min:1',
                'nama.*' => 'required|string|max:100',
                'tunjangan' => 'nullable|array',
                'tunjangan.*' => 'nullable|numeric|min:0',
            ], [
                'kode.required' => 'Kode wajib ada',
                'kode.*.required' => 'Kode tidak boleh kosong',

                'nama.required' => 'Nama tunjangan wajib ada',
                'nama.*.required' => 'Nama tunjangan tidak boleh kosong',

                'tunjangan.numeric' => 'Tunjangan harus berupa angka',
                'tunjangan.min' => 'Tunjangan tidak boleh kurang dari 0',
            ]);

            // ✅ KIRIM KE SERVICE (JANGAN PAKAI KODE DARI FRONTEND!)
            $result = $this->profesiService->create($request);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Profesi berhasil disimpan',
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

    public function edit(string $id)
    {
        $dataProfesi = $this->profesiService->findById($id);
        return response()->json($dataProfesi);
    }

    public function update(Request $request, string $id)
    {
        try {
            Log::info('Menerima data untuk update:', ['id' => $id, 'data' => $request->all()]);
            // ✅ VALIDASI SINGLE
            $validated = $request->validate([
                'nama' => 'required|string|max:100|unique:master_tunjangan,nama,' . $id,
                'tunjangan' => 'required|numeric|min:0'
            ], [
                'nama.required' => 'Nama tunjangan wajib diisi',
                'nama.unique' => 'Nama tunjangan sudah digunakan',

                'tunjangan.required' => 'Tunjangan wajib diisi',
                'tunjangan.numeric' => 'Tunjangan harus angka'
            ]);

            $result = $this->profesiService->update($id, $validated);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Profesi berhasil diperbarui',
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

    public function destroy($id)
    {
        try {

            $this->profesiService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data Profesi berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
