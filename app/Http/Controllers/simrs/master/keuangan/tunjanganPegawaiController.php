<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Import\Keuangan\master\tunjanganPegawaiImport;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsTunjanganModel;
use App\Models\dbSimrs\tunjanganPegawaiModel;
use App\Services\masterData\tunjanganPegawaiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class tunjanganPegawaiController extends Controller
{

    protected $tunjanganPegawaiService;
    public function __construct(tunjanganPegawaiService $tunjanganPegawaiService)
    {
        $this->tunjanganPegawaiService = $tunjanganPegawaiService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("simrs.masterData.Keuangan.tunjanganPegawai.tunjanganPegawai");
    }

    public function guideJenisTunjangan()
    {
        return response()->json($this->tunjanganPegawaiService->guideJenisTunjangan());
    }

    public function getPegawai()
    {
        return response()->json($this->tunjanganPegawaiService->getPegawai());
    }

    public function getTunjanganPegawaiTable()
    {
        $data = $this->tunjanganPegawaiService->getTunjanganPegawaiTable();

        return DataTables::of($data)
            ->addIndexColumn()
            ->rawColumns([
                'status',
                'tunjangan',
                'total',
                'jabatan',
                'actions'
            ])

            ->make(true);
    }

    public function exportTemplate()
    {
        return $this->tunjanganPegawaiService->exportTemplate();
    }

    public function importTunjanganPegawai(Request $request)
    {
        try {

            $request->validate([
                'file' => 'required|mimes:xls,xlsx|max:5120'
            ]);

            // reset counter di service
            $this->tunjanganPegawaiService->resetCounter();

            Excel::import(
                new tunjanganPegawaiImport($this->tunjanganPegawaiService),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'added' => $this->tunjanganPegawaiService->getAdded(),
                'skipped' => $this->tunjanganPegawaiService->getSkipped()
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {

            // VALIDASI
            $validated = $request->validate([
                'nik' => 'required',

                'tunjangan_id' => 'required|array|min:1',
                'tunjangan_id.*' => 'required|exists:master_tunjangan,id',

            ], [
                'nik.required' => 'Pegawai wajib dipilih',

                'tunjangan_id.required' => 'Tunjangan wajib diisi',
                'tunjangan_id.*.required' => 'Tunjangan tidak boleh kosong',
                'tunjangan_id.*.exists' => 'Tunjangan tidak valid',
            ]);

            // VALIDASI TAMBAHAN (ANTI DUPLICATE)
            if (count($validated['tunjangan_id']) !== count(array_unique($validated['tunjangan_id']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tunjangan tidak boleh duplikat'
                ], 422);
            }

            // KIRIM KE SERVICE
            $result = $this->tunjanganPegawaiService->create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Tunjangan pegawai berhasil disimpan',
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

    public function updateInline(Request $request, $id)
    {
        try {

            $request->validate([
                'nominal' => 'required|numeric|min:0'
            ]);

            $this->tunjanganPegawaiService->updateNominal($id, $request->nominal);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil update'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {

            $validated = $request->validate([
                'data' => 'required|array|min:1',
                'data.*' => 'required|numeric|min:0'
            ]);

            $this->tunjanganPegawaiService->bulkUpdate($validated['data']);

            return response()->json([
                'status' => true,
                'message' => 'Bulk update berhasil'
            ]);

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

            $this->tunjanganPegawaiService->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getByPegawai($nik)
    {
        $data = $this->tunjanganPegawaiService->getByPegawai($nik);

        return response()->json($data);
    }

    public function previewDistribusi(Request $request)
    {
        $request->validate([
            'sumber' => 'required',
            'tujuan' => 'required|array|min:1',
            'tunjangan_id' => 'required|array|min:1',
        ]);

        $result = [];

        // ambil semua pegawai tujuan sekaligus (biar hemat query 🔥)
        $pegawaiList = gapokModel::whereIn('nik', $request->tujuan)
            ->pluck('nama', 'nik');

        // ambil semua tunjangan sekaligus
        $tunjanganList = jnsTunjanganModel::whereIn('id', $request->tunjangan_id)
            ->pluck('nama', 'id');

        // ambil semua existing data (biar tidak query per loop 🔥)
        $existing = tunjanganPegawaiModel::whereIn('nik', $request->tujuan)
            ->whereIn('tunjangan_id', $request->tunjangan_id)
            ->get()
            ->map(function ($item) {
                return $item->nik . '-' . $item->tunjangan_id;
            })
            ->toArray();

        foreach ($request->tujuan as $nik) {

            foreach ($request->tunjangan_id as $tid) {

                $key = $nik . '-' . $tid;

                $isExists = in_array($key, $existing);

                $result[] = [
                    'nik' => $nik,
                    'nama_pegawai' => $pegawaiList[$nik] ?? $nik,
                    'tunjangan_id' => $tid,
                    'nama_tunjangan' => $tunjanganList[$tid] ?? '-',
                    'status' => $isExists ? 'exists' : 'new'
                ];
            }
        }

        return response()->json($result);
    }

    public function distribusi(Request $request)
    {
        try {

        Log::info('Data yang akan didistribusi: '. json_encode($request->all()));

            // =========================
            // VALIDASI
            // =========================
            $validated = $request->validate([
                'sumber' => 'required|string|exists:gaji_pokok,nik',
                'tujuan' => 'required|array|min:1',
                'tujuan.*' => 'required|string|exists:gaji_pokok,nik',
                'tunjangan_id' => 'required|array|min:1',
                'tunjangan_id.*' => 'required|exists:master_tunjangan,id',
            ], [
                'sumber.required' => 'Pegawai sumber wajib dipilih',
                'tujuan.required' => 'Pegawai tujuan wajib dipilih',
                'tunjangan_id.required' => 'Tunjangan wajib dipilih'
            ]);

            // =========================
            // PROSES KE SERVICE
            // =========================
            $result = $this->tunjanganPegawaiService->distribusiSelective($validated);

            return response()->json([
                'status' => true,
                'message' => 'Distribusi berhasil',
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
}
