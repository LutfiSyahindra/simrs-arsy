<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\skorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class skorController extends Controller
{
    protected $skorService;
    public function __construct(skorService $skorService)
    {
        $this->skorService = $skorService;
    }
    /**
     * Display a listing of the resource.
     */
    public function skor()
    {
        return view("simrs.masterData.Keuangan.skor.skor");
    }

    public function generateKodeSkor()
    {
        return response()->json($this->skorService->generateKodeSkor());
    }

    public function skorTable()
    {
        $data = $this->skorService->skorTable();
        
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('bobot_skor', function ($row) {
                return number_format($row['bobot_skor'], 0, ',', '.');
            })
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editSkor('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteSkor('.$row['id'].')">
                        <i class="mdi mdi-trash-can"></i>
                    </button>

                </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

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
                'bobot' => 'nullable|array',
                'bobot.*' => 'nullable|numeric|min:0',
            ], [
                'kode.required' => 'Kode wajib ada',
                'kode.*.required' => 'Kode tidak boleh kosong',

                'jenis.required' => 'Jenis skor wajib ada',
                'jenis.*.required' => 'Jenis skor tidak boleh kosong',

                'nama.required' => 'Nama skor wajib ada',
                'nama.*.required' => 'Nama skor tidak boleh kosong',

                'bobot.numeric' => 'Bobot harus berupa angka',
                'bobot.min' => 'Bobot tidak boleh kurang dari 0',
            ]);

            // ✅ KIRIM KE SERVICE (JANGAN PAKAI KODE DARI FRONTEND!)
            $result = $this->skorService->create($request);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Skor berhasil disimpan',
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

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
        $dataSkor = $this->skorService->findById($id);
        return response()->json($dataSkor);
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
            $bobot = $request->input('bobot');

            $jenis = is_array($jenis) ? ($jenis[0] ?? null) : $jenis;
            $nama = is_array($nama) ? ($nama[0] ?? null) : $nama;
            $bobot = is_array($bobot) ? ($bobot[0] ?? null) : $bobot;

            $validated = validator([
                'jenis' => $jenis,
                'nama' => $nama,
                'bobot' => $bobot,
            ], [
                'jenis' => 'required|string|max:100',
                'nama' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('master_skor', 'keterangan')
                        ->ignore($id)
                        ->where(fn ($query) => $query->where('jenis', $jenis)),
                ],
                'bobot' => 'required|numeric|min:0',
            ], [
                'nama.required' => 'Nama skor wajib diisi',
                'nama.unique' => 'Nama skor sudah digunakan untuk jenis skor ini',
                'nama.max' => 'Nama skor tidak boleh lebih dari 150 karakter',

                'bobot.required' => 'Bobot wajib diisi',
                'bobot.numeric' => 'Bobot harus angka',
                'bobot.min' => 'Bobot tidak boleh kurang dari 0',

                'jenis.required' => 'Jenis skor wajib diisi',
                'jenis.string' => 'Jenis skor harus berupa teks',
                'jenis.max' => 'Jenis skor tidak boleh lebih dari 100 karakter',
            ])->validate();

            $result = $this->skorService->update($id, [
                'jenis' => $validated['jenis'],
                'keterangan' => $validated['nama'],
                'bobot_skor' => $validated['bobot'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Skor berhasil diperbarui',
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

            $this->skorService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data Skor berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
