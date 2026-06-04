<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\jnsTindakanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class jnsTindakanController extends Controller
{
    protected $jnsTindakanService;
    public function __construct(jnsTindakanService $jnsTindakanService)
    {
        $this->jnsTindakanService = $jnsTindakanService;
    }
    /**
     * Display a listing of the resource.
     */
    public function jnsTindakan()
    {
        return view('simrs.masterData.Keuangan.jenisTindakan.jnsTindakan');
    }

    public function generateKodeJnsTindakan()
    {
        return response()->json($this->jnsTindakanService->generateKodeJnsTindakan());
    }

    public function jnsTindakanTable()
    {
        $data = $this->jnsTindakanService->jnsTindakanTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editJnsTindakan('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteJnsTindakan('.$row['id'].')">
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
            $validated = $request->validate([
                'jenis' => 'required|array|min:1',
                'jenis.*' => 'required|string|max:100',
            ], [
                'jenis.required' => 'Jenis Tindakan wajib ada',
                'jenis.*.required' => 'Jenis Tindakan tidak boleh kosong',
            ]);

            $result = $this->jnsTindakanService->create($validated['jenis']);

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
        $dataJnsTindakan = $this->jnsTindakanService->findById($id);
        return response()->json($dataJnsTindakan);
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

            $result = $this->jnsTindakanService->update($id, [
                'jenis' => $validated['jenis'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Tindakan berhasil diperbarui',
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
     * Update the specified resource in storage.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $this->jnsTindakanService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data Jenis Tindakan berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
