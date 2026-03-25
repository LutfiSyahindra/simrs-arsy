<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\jnsTunjanganService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class jenisTunjanganController extends Controller
{

    protected $jnsTunjanganService;
    public function __construct(jnsTunjanganService $jnsTunjanganService)
    {
        $this->jnsTunjanganService = $jnsTunjanganService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("simrs.masterData.Keuangan.jenisTunjangan.jenisTunjangan");
    }

    public function generateKodeTunjangan()
    {
        return response()->json($this->jnsTunjanganService->generateKodeTunjangan());
    }

    public function getJnsTunjanganTable()
    {
        $data = $this->jnsTunjanganService->getJnsTunjanganTable();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editJnsTunjangan('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteJnsTunjangan('.$row['id'].')">
                        <i class="mdi mdi-trash-can"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
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

            // ✅ VALIDASI ARRAY
            $validated = $request->validate([
                'kode' => 'required|array|min:1',
                'kode.*' => 'required|string|max:20',
                'nama' => 'required|array|min:1',
                'nama.*' => 'required|string|max:100',
                'persentase' => 'nullable|array',
                'persentase.*' => 'nullable|numeric|min:0|max:100',
            ], [
                'kode.required' => 'Kode wajib ada',
                'kode.*.required' => 'Kode tidak boleh kosong',

                'nama.required' => 'Nama tunjangan wajib ada',
                'nama.*.required' => 'Nama tunjangan tidak boleh kosong',

                'persentase.numeric' => 'Persentase harus berupa angka',
                'persentase.min' => 'Persentase tidak boleh kurang dari 0',
                'persentase.max' => 'Persentase tidak boleh lebih dari 100',
            ]);

            // ✅ KIRIM KE SERVICE (JANGAN PAKAI KODE DARI FRONTEND!)
            $result = $this->jnsTunjanganService->create($request);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Tunjangan berhasil disimpan',
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
        $dataJnsTunjangan = $this->jnsTunjanganService->findById($id);
        return response()->json($dataJnsTunjangan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // ✅ VALIDASI SINGLE
            $validated = $request->validate([
                'nama' => 'required|string|max:100|unique:master_tunjangan,nama,' . $id,
                'persentase' => 'required|numeric|min:0|max:100'
            ], [
                'nama.required' => 'Nama tunjangan wajib diisi',
                'nama.unique' => 'Nama tunjangan sudah digunakan',

                'persentase.required' => 'Persentase wajib diisi',
                'persentase.numeric' => 'Persentase harus angka'
            ]);

            $result = $this->jnsTunjanganService->update($id, $validated);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Tunjangan berhasil diperbarui',
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
    public function destroy($id)
    {
        try {

            $this->jnsTunjanganService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data jenis tunjangan berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
