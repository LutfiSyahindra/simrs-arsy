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
                'tipe' => 'nullable|array',
                'tipe.*' => 'nullable|string|in:jabatan,profesi,anak,pasangan,masa_kerja,custom',
                'nilai' => 'nullable|array',
                'nilai.*' => 'nullable|numeric|min:0',
            ], [
                'kode.required' => 'Kode wajib ada',
                'kode.*.required' => 'Kode tidak boleh kosong',

                'nama.required' => 'Nama tunjangan wajib ada',
                'nama.*.required' => 'Nama tunjangan tidak boleh kosong',

                'tipe.*.in' => 'Tipe tunjangan tidak valid',
                'nilai.*.numeric' => 'Nilai harus berupa angka',
                'nilai.*.min' => 'Nilai tidak boleh kurang dari 0',
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

            // =========================
            // VALIDASI DASAR
            // =========================
            $validated = $request->validate([
                'nama' => 'required|string|max:100|unique:master_tunjangan,nama,' . $id,
                'tipe' => 'required|string|in:jabatan,profesi,anak,pasangan,masa_kerja,custom',
                'nilai' => 'nullable|numeric|min:0'
            ], [
                'nama.required' => 'Nama tunjangan wajib diisi',
                'nama.unique' => 'Nama tunjangan sudah digunakan',

                'tipe.required' => 'Tipe tunjangan wajib diisi',
                'nilai.numeric' => 'Nilai harus angka'
            ]);

            // =========================
            // VALIDASI LOGIC BERDASARKAN TIPE
            // =========================
            $tipe = $request->tipe;
            $nilai = $request->nilai;

            // 🔥 jabatan & profesi → tidak boleh ada nilai
            if (in_array($tipe, ['jabatan', 'profesi'])) {
                $validated['nilai'] = null;
            }

            // 🔥 anak & pasangan → wajib persen
            if (in_array($tipe, ['anak', 'pasangan'])) {

                if ($nilai === null) {
                    return response()->json([
                        'status' => false,
                        'errors' => [
                            'nilai' => ['Nilai wajib diisi untuk tipe ini']
                        ]
                    ], 422);
                }

                if ($nilai > 100) {
                    return response()->json([
                        'status' => false,
                        'errors' => [
                            'nilai' => ['Persentase tidak boleh lebih dari 100%']
                        ]
                    ], 422);
                }
            }

            // 🔥 masa kerja → nominal (bukan persen)
            if ($tipe === 'masa_kerja') {

                if ($nilai === null) {
                    return response()->json([
                        'status' => false,
                        'errors' => [
                            'nilai' => ['Nominal per tahun wajib diisi']
                        ]
                    ], 422);
                }

                if ($nilai < 1000) {
                    return response()->json([
                        'status' => false,
                        'errors' => [
                            'nilai' => ['Nominal terlalu kecil (minimal 1000)']
                        ]
                    ], 422);
                }
            }

            // =========================
            // UPDATE
            // =========================
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
