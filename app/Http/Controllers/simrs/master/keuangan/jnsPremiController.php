<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\jnsPremiService;
// use App\Services\masterData\jnsPremiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class jnsPremiController extends Controller
{
    protected $jnsPremiService;
    public function __construct(jnsPremiService $jnsPremiService)
    {
        $this->jnsPremiService = $jnsPremiService;
    }

    public function jnsPremi()
    {
        return view('simrs.masterData.Keuangan.jenisPremi.jnsPremi');
    }

    public function generateKodeJnsPremi()
    {
        return response()->json($this->jnsPremiService->generateKodeJnsPremi());
    }

    public function jnsPremiTable()
    {
        $data = $this->jnsPremiService->jnsPremiTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editJnsPremi('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteJnsPremi('.$row['id'].')">
                        <i class="mdi mdi-trash-can"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'jenis' => 'required|array|min:1',
                'jenis.*' => 'required|string|max:100',
            ], [
                'jenis.required' => 'Jenis Premi wajib ada',
                'jenis.*.required' => 'Jenis Premi tidak boleh kosong',
            ]);

            $result = $this->jnsPremiService->create($validated['jenis']);

            return response()->json([
                'status' => true,
                'message' => 'Jenis tindakan berhasil disimpan',
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

    public function show(string $id)
    {
    }

    public function edit(string $id)
    {
        $dataJnsPremi = $this->jnsPremiService->findById($id);
        return response()->json($dataJnsPremi);
    }

    public function update(Request $request, string $id)
    {
        try {
            $jenis = $request->input('jenis');
            $jenis = is_array($jenis) ? ($jenis[0] ?? null) : $jenis;

            $validated = validator([
                'jenis' => $jenis,
            ], [
                'jenis' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('master_jenis_tindakan', 'jenis')->ignore($id),
                ],
            ], [
                'jenis.required' => 'Jenis tindakan wajib diisi',
                'jenis.string' => 'Jenis tindakan harus berupa teks',
                'jenis.max' => 'Jenis tindakan tidak boleh lebih dari 100 karakter',
                'jenis.unique' => 'Jenis tindakan sudah digunakan',
            ])->validate();

            $result = $this->jnsPremiService->update($id, [
                'jenis' => $validated['jenis'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Premi berhasil diperbarui',
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

            $this->jnsPremiService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data Jenis Premi berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
