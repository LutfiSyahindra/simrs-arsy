<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\unitService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class unitController extends Controller
{
    protected $unitService;
    public function __construct(unitService $unitService)
    {
        $this->unitService = $unitService;
    }
    /**
     * Display a listing of the resource.
     */
    public function unit()
    {
        return view('simrs.masterData.Keuangan.unit.unit');
    }


    public function generateKodeUnit()
    {
        return response()->json($this->unitService->generateKodeUnit());
    }

    public function unitTable()
    {
        $data = $this->unitService->unitTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editUnit('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteUnit('.$row['id'].')">
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
                'jenis' => 'required|array|min:1',
                'jenis.*' => 'required|string|max:100',
                'nama' => 'required|array|min:1',
                'nama.*' => 'required|string|max:100',
            ], [
                'kode.required' => 'Kode wajib ada',
                'kode.*.required' => 'Kode tidak boleh kosong',

                'jenis.required' => 'Jenis unit wajib ada',
                'jenis.*.required' => 'Jenis unit tidak boleh kosong',

                'nama.required' => 'Nama unit wajib ada',
                'nama.*.required' => 'Nama unit tidak boleh kosong',
            ]);

            // ✅ KIRIM KE SERVICE (JANGAN PAKAI KODE DARI FRONTEND!)
            $result = $this->unitService->create($request);

            return response()->json([
                'status' => true,
                'message' => 'Jenis unit berhasil disimpan',
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
        $dataUnit = $this->unitService->findById($id);
        return response()->json($dataUnit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // ✅ VALIDASI SINGLE
            $jenis = $request->input('jenis');
            $nama = $request->input('nama');

            $jenis = is_array($jenis) ? ($jenis[0] ?? null) : $jenis;
            $nama = is_array($nama) ? ($nama[0] ?? null) : $nama;

            $validated = validator([
                'jenis' => $jenis,
                'nama' => $nama,
            ], [
                'jenis' => 'required|string|max:100',
                'nama' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('master_unit', 'keterangan')
                        ->ignore($id)
                        ->where(fn ($query) => $query->where('jenis', $jenis)),
                ],
            ], [
                'nama.required' => 'Nama unit wajib diisi',
                'nama.unique' => 'Nama unit sudah digunakan untuk jenis unit ini',
                'nama.max' => 'Nama unit tidak boleh lebih dari 150 karakter',

                'jenis.required' => 'Jenis unit wajib diisi',
                'jenis.string' => 'Jenis unit harus berupa teks',
                'jenis.max' => 'Jenis unit tidak boleh lebih dari 100 karakter',
            ])->validate();

            $result = $this->unitService->update($id, [
                'jenis' => $validated['jenis'],
                'keterangan' => $validated['nama'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Unit berhasil diperbarui',
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
        //
    }
}
