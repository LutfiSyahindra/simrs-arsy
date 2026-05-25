<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Import\Keuangan\master\tunjanganPegawaiImport;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jabatanModel;
use App\Models\dbSimrs\jnsTunjanganModel;
use App\Models\dbSimrs\profesiModel;
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
        $tunjangan = jnsTunjanganModel::select('kode','nama')->get();
        $jabatan   = jabatanModel::select('kode','nama')->get();
        $profesi   = profesiModel::select('kode','nama')->get();

        return view('simrs.masterData.Keuangan.tunjanganPegawai.tunjanganPegawai', compact('tunjangan','jabatan','profesi'));
    }

    public function guideJenisTunjangan()
    {
        $tunjangan = $this->tunjanganPegawaiService->guideJenisTunjangan();
        return response()->json($tunjangan);
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
            Log::info($request->all());

            // ================= VALIDASI =================
            $validated = $request->validate([
                'nik' => 'required|exists:gaji_pokok,nik',

                'tunjangan_id' => 'required|array|min:1',
                'tunjangan_id.*' => 'nullable|exists:master_tunjangan,id',

                'referensi_id' => 'nullable|array',
                'referensi_id.*' => 'nullable',
                'qty' => 'nullable|array',
                'qty.*' => 'nullable|integer|min:0|max:3',
                'nominal' => 'required|array',
                'nominal.*' => 'nullable|numeric|min:0',
            ], [
                'nik.required' => 'Pegawai wajib dipilih',
                'tunjangan_id.required' => 'Tunjangan wajib diisi',
            ]);

            // ================= BERSIHKAN DATA =================
            $data = [];

            foreach ($request->tunjangan_id as $i => $tunjanganId) {

                if (!$tunjanganId) continue; // 🔥 skip NULL

                $data[] = [
                    'nik' => $request->nik,
                    'tunjangan_id' => $tunjanganId,
                    'referensi_id' => $request->referensi_id[$i] ?? null,
                    'qty' => $request->qty[$i] ?? null,
                    'nominal' => is_numeric($request->nominal[$i] ?? null)
                        ? $request->nominal[$i]
                        : 0,
                ];
            }

            // ================= ANTI DUPLICATE =================
            $ids = array_column($data, 'tunjangan_id');

            if (count($ids) === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tunjangan wajib diisi',
                    'errors' => [
                        'tunjangan_id' => ['Tunjangan wajib diisi']
                    ]
                ], 422);
            }

            if (count($ids) !== count(array_unique($ids))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tunjangan tidak boleh duplikat',
                    'errors' => [
                        'tunjangan_id' => ['Tunjangan tidak boleh duplikat']
                    ]
                ], 422);
            }

            $existingIds = tunjanganPegawaiModel::where('nik', $request->nik)
                ->whereIn('tunjangan_id', $ids)
                ->pluck('tunjangan_id')
                ->toArray();

            if (!empty($existingIds)) {
                $existingNames = jnsTunjanganModel::whereIn('id', $existingIds)
                    ->pluck('nama')
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Tunjangan sudah ada untuk pegawai ini: ' . $existingNames,
                    'errors' => [
                        'tunjangan_id' => ['Tunjangan sudah ada untuk pegawai ini: ' . $existingNames]
                    ]
                ], 422);
            }

            // ================= KIRIM KE SERVICE =================
            $result = $this->tunjanganPegawaiService->create($data);

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

        Log::info([
            'request' => $request->all(),
        ]);

            $data = [];

            // ================= AMBIL DATA LAMA =================
            $row = $this->tunjanganPegawaiService->findById($id);

            if (!$row) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // ================= UPDATE FIELD =================

            if ($request->has('nominal')) {
                $request->validate([
                    'nominal' => 'numeric|min:0'
                ]);
                $data['nominal'] = $request->nominal;
            }

            if ($request->has('referensi_id')) {
                $data['referensi_id'] = $request->referensi_id;
            }

            if ($request->has('qty')) {
                $request->validate([
                    'qty' => 'integer|min:0|max:3'
                ]);
                $data['qty'] = $request->qty;
            }

            // ================= HITUNG ULANG NOMINAL =================

            $jenis = $row->jenisTunjangan->tipe;
            $gapok = $row->gapok->gapok ?? 0;

            $nominalBaru = $row->nominal;

            switch ($jenis) {

                case 'jabatan':
                    if ($request->has('referensi_id')) {
                        $jabatan = $this->tunjanganPegawaiService
                            ->getJabatanById($request->referensi_id);

                        $nominalBaru = $jabatan->tunjangan ?? 0;
                    }
                    break;

                case 'profesi':
                    if ($request->has('referensi_id')) {
                        $profesi = $this->tunjanganPegawaiService
                            ->getProfesiById($request->referensi_id);

                        $nominalBaru = $profesi->tunjangan ?? 0;
                    }
                    break;

                    case 'anak':

                    $qty = (int) $request->qty;
                    $qty = min($qty, 3);

                    $gapok = (int) ($row->gapok->gaji_pokok ?? 0);
                    $persen = (int) ($row->jenisTunjangan->nilai ?? 0);

                    $nominalBaru = $qty * ($gapok * $persen / 100);

                    Log::info([
                        'DEBUG_ANAK' => [
                            'qty' => $qty,
                            'gapok' => $gapok,
                            'persen' => $persen,
                            'hasil' => $nominalBaru
                        ]
                    ]);

                    $data['qty'] = $qty;
                    $data['nominal'] = $nominalBaru;

                    break;

                case 'pasangan':
                    $persen = $row->jenisTunjangan->nilai ?? 0;
                    $nominalBaru = $gapok * $persen / 100;
                    break;

                case 'masa_kerja':
                    $masaKerja = $row->masa_kerja ?? 0;
                    $tarif = $row->jenisTunjangan->nilai ?? 0;

                    $nominalBaru = $masaKerja * $tarif;
                    break;
            }

            // 🔥 override nominal kalau bukan manual
            if (!$request->has('nominal')) {
                $data['nominal'] = $nominalBaru;
            }

            // ================= UPDATE =================
            $this->tunjanganPegawaiService->updateInline($id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil update',
                'nominal' => $data['nominal'] ?? $row->nominal
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

    public function getJabatan()
    {
        return response()->json(
            $this->tunjanganPegawaiService->getListJabatan()
        );
    }

    public function getGapokById($nik)
    {
        return response()->json(
            $this->tunjanganPegawaiService->getGapokById($nik)
        );
    }

    public function getProfesi()
    {
        return response()->json(
            $this->tunjanganPegawaiService->getListProfesi()
        );
    }
}
