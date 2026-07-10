<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Services\masterData\jnsPotonganService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class jenisPotonganController extends Controller
{
    protected $jnsPotonganService;

    public function __construct(jnsPotonganService $jnsPotonganService)
    {
        $this->jnsPotonganService = $jnsPotonganService;
    }

    public function index()
    {
        return view('simrs.masterData.Keuangan.jenisPotongan.jenisPotongan');
    }

    public function generateKodePotongan()
    {
        return response()->json($this->jnsPotonganService->generateKodePotongan());
    }

    public function getJnsPotonganTable()
    {
        $data = $this->jnsPotonganService->getJnsPotonganTable();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary"
                        title="Edit"
                        onclick="editJnsPotongan('.$row['id'].')">
                        <i class="mdi mdi-eye"></i>
                    </button>

                    <button class="btn btn-outline-danger"
                        title="Hapus"
                        onclick="deleteJnsPotongan('.$row['id'].')">
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
            $request->validate([
                'kode' => 'required|array|min:1',
                'kode.*' => 'required|string|max:20',
                'nama' => 'required|array|min:1',
                'nama.*' => 'required|string|max:100',
                'tipe' => 'required|array|min:1',
                'tipe.*' => 'required|string|in:manual,nominal,persen_gapok,persen_total_gaji',
                'nilai' => 'nullable|array',
                'nilai.*' => 'nullable|numeric|min:0',
                'keterangan' => 'nullable|array',
                'keterangan.*' => 'nullable|string|max:255',
            ], [
                'kode.required' => 'Kode wajib ada',
                'nama.required' => 'Nama potongan wajib ada',
                'nama.*.required' => 'Nama potongan tidak boleh kosong',
                'tipe.*.in' => 'Tipe potongan tidak valid',
                'nilai.*.numeric' => 'Nilai harus berupa angka',
            ]);

            $this->validateNilaiByTipe($request);

            $result = $this->jnsPotonganService->create($request);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Potongan berhasil disimpan',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit(string $id)
    {
        $dataJnsPotongan = $this->jnsPotonganService->findById($id);

        return response()->json($dataJnsPotongan);
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:100|unique:master_potongan,nama,'.$id,
                'tipe' => 'required|string|in:manual,nominal,persen_gapok,persen_total_gaji',
                'nilai' => 'nullable|numeric|min:0',
                'keterangan' => 'nullable|string|max:255',
            ], [
                'nama.required' => 'Nama potongan wajib diisi',
                'nama.unique' => 'Nama potongan sudah digunakan',
                'tipe.required' => 'Tipe potongan wajib diisi',
                'nilai.numeric' => 'Nilai harus angka',
            ]);

            if ($validated['tipe'] !== 'manual' && ! is_numeric($request->nilai)) {
                throw ValidationException::withMessages([
                    'nilai' => ['Nilai wajib diisi untuk tipe ini'],
                ]);
            }

            if ($this->isPersenTipe($validated['tipe']) && (float) $request->nilai > 100) {
                throw ValidationException::withMessages([
                    'nilai' => ['Persentase tidak boleh lebih dari 100%'],
                ]);
            }

            $result = $this->jnsPotonganService->update($id, $validated);

            return response()->json([
                'status' => true,
                'message' => 'Jenis Potongan berhasil diperbarui',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->jnsPotonganService->delete($id);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Data jenis potongan berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateNilaiByTipe(Request $request): void
    {
        $nilaiList = $request->input('nilai', []);

        foreach ($request->tipe as $i => $tipe) {
            $nilai = $nilaiList[$i] ?? null;

            if ($tipe !== 'manual' && ! is_numeric($nilai)) {
                throw ValidationException::withMessages([
                    "nilai.$i" => ['Nilai wajib diisi untuk tipe ini'],
                ]);
            }

            if ($this->isPersenTipe($tipe) && (float) $nilai > 100) {
                throw ValidationException::withMessages([
                    "nilai.$i" => ['Persentase tidak boleh lebih dari 100%'],
                ]);
            }
        }
    }

    private function isPersenTipe(string $tipe): bool
    {
        return in_array($tipe, ['persen_gapok', 'persen_total_gaji'], true);
    }
}
