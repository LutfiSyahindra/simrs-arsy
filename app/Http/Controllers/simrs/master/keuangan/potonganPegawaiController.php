<?php

namespace App\Http\Controllers\simrs\master\keuangan;

use App\Http\Controllers\Controller;
use App\Import\Keuangan\master\potonganPegawaiImport;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsPotonganModel;
use App\Models\dbSimrs\potonganPegawaiModel;
use App\Services\masterData\potonganPegawaiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class potonganPegawaiController extends Controller
{
    protected $potonganPegawaiService;

    public function __construct(potonganPegawaiService $potonganPegawaiService)
    {
        $this->potonganPegawaiService = $potonganPegawaiService;
    }

    public function index()
    {
        $potongan = jnsPotonganModel::select('kode', 'nama', 'tipe', 'nilai')->get();

        return view('simrs.masterData.Keuangan.potonganPegawai.potonganPegawai', compact('potongan'));
    }

    public function guideJenisPotongan()
    {
        return response()->json($this->potonganPegawaiService->guideJenisPotongan());
    }

    public function getPegawai()
    {
        return response()->json($this->potonganPegawaiService->getPegawai());
    }

    public function getPotonganPegawaiTable()
    {
        $data = $this->potonganPegawaiService->getPotonganPegawaiTable();

        return DataTables::of($data)
            ->addIndexColumn()
            ->rawColumns([
                'status',
                'potongan',
                'total',
                'jabatan',
                'actions',
            ])
            ->make(true);
    }

    public function exportTemplate()
    {
        return $this->potonganPegawaiService->exportTemplate();
    }

    public function importPotonganPegawai(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xls,xlsx|max:5120',
            ]);

            $this->potonganPegawaiService->resetCounter();

            Excel::import(
                new potonganPegawaiImport($this->potonganPegawaiService),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'added' => $this->potonganPegawaiService->getAdded(),
                'skipped' => $this->potonganPegawaiService->getSkipped(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info($request->all());

            $request->validate([
                'nik' => 'required|exists:gaji_pokok,nik',
                'potongan_id' => 'required|array|min:1',
                'potongan_id.*' => 'nullable|exists:master_potongan,id',
                'nominal' => 'required|array',
                'nominal.*' => 'nullable|numeric|min:0',
            ], [
                'nik.required' => 'Pegawai wajib dipilih',
                'potongan_id.required' => 'Potongan wajib diisi',
            ]);

            $data = [];

            foreach ($request->potongan_id as $i => $potonganId) {
                if (! $potonganId) {
                    continue;
                }

                $data[] = [
                    'nik' => $request->nik,
                    'potongan_id' => $potonganId,
                    'nominal' => is_numeric($request->nominal[$i] ?? null)
                        ? $request->nominal[$i]
                        : 0,
                ];
            }

            $ids = array_column($data, 'potongan_id');

            if (count($ids) === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Potongan wajib diisi',
                    'errors' => [
                        'potongan_id' => ['Potongan wajib diisi'],
                    ],
                ], 422);
            }

            if (count($ids) !== count(array_unique($ids))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Potongan tidak boleh duplikat',
                    'errors' => [
                        'potongan_id' => ['Potongan tidak boleh duplikat'],
                    ],
                ], 422);
            }

            $existingIds = potonganPegawaiModel::where('nik', $request->nik)
                ->whereIn('potongan_id', $ids)
                ->pluck('potongan_id')
                ->toArray();

            if (! empty($existingIds)) {
                $existingNames = jnsPotonganModel::whereIn('id', $existingIds)
                    ->pluck('nama')
                    ->implode(', ');

                return response()->json([
                    'status' => false,
                    'message' => 'Potongan sudah ada untuk pegawai ini: '.$existingNames,
                    'errors' => [
                        'potongan_id' => ['Potongan sudah ada untuk pegawai ini: '.$existingNames],
                    ],
                ], 422);
            }

            foreach ($data as $row) {
                $master = jnsPotonganModel::find($row['potongan_id']);

                if (($master->tipe ?? null) === 'manual' && (float) $row['nominal'] <= 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Nominal potongan manual wajib diisi',
                        'errors' => [
                            'nominal' => ['Nominal potongan manual wajib diisi'],
                        ],
                    ], 422);
                }
            }

            $result = $this->potonganPegawaiService->create($data);

            return response()->json([
                'status' => true,
                'message' => 'Potongan pegawai berhasil disimpan',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function updateInline(Request $request, $id)
    {
        try {
            $row = $this->potonganPegawaiService->findById($id);

            if (! $row) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data = [];
            $jenis = $row->jenisPotongan;

            if (($jenis->tipe ?? null) === 'manual') {
                $request->validate([
                    'nominal' => 'required|numeric|min:0',
                ]);

                $data['nominal'] = $request->nominal;
            } else {
                $data['nominal'] = $this->potonganPegawaiService
                    ->calculateNominal($jenis, $row->gapok, $row->nominal);
            }

            $data['updated_at'] = now();
            $this->potonganPegawaiService->updateInline($id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil update',
                'nominal' => $data['nominal'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'data' => 'required|array|min:1',
                'data.*' => 'required|numeric|min:0',
            ]);

            $this->potonganPegawaiService->bulkUpdate($validated['data']);

            return response()->json([
                'status' => true,
                'message' => 'Bulk update berhasil',
            ]);
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
            $this->potonganPegawaiService->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByPegawai($nik)
    {
        return response()->json($this->potonganPegawaiService->getByPegawai($nik));
    }

    public function previewDistribusi(Request $request)
    {
        $request->validate([
            'sumber' => 'required',
            'tujuan' => 'required|array|min:1',
            'potongan_id' => 'required|array|min:1',
        ]);

        $result = [];

        $pegawaiList = gapokModel::whereIn('nik', $request->tujuan)
            ->pluck('nama', 'nik');

        $potonganList = jnsPotonganModel::whereIn('id', $request->potongan_id)
            ->pluck('nama', 'id');

        $existing = potonganPegawaiModel::whereIn('nik', $request->tujuan)
            ->whereIn('potongan_id', $request->potongan_id)
            ->get()
            ->map(function ($item) {
                return $item->nik.'-'.$item->potongan_id;
            })
            ->toArray();

        foreach ($request->tujuan as $nik) {
            foreach ($request->potongan_id as $pid) {
                $key = $nik.'-'.$pid;
                $isExists = in_array($key, $existing);

                $result[] = [
                    'nik' => $nik,
                    'nama_pegawai' => $pegawaiList[$nik] ?? $nik,
                    'potongan_id' => $pid,
                    'nama_potongan' => $potonganList[$pid] ?? '-',
                    'status' => $isExists ? 'exists' : 'new',
                ];
            }
        }

        return response()->json($result);
    }

    public function distribusi(Request $request)
    {
        try {
            $validated = $request->validate([
                'sumber' => 'required|string|exists:gaji_pokok,nik',
                'tujuan' => 'required|array|min:1',
                'tujuan.*' => 'required|string|exists:gaji_pokok,nik',
                'potongan_id' => 'required|array|min:1',
                'potongan_id.*' => 'required|exists:master_potongan,id',
            ], [
                'sumber.required' => 'Pegawai sumber wajib dipilih',
                'tujuan.required' => 'Pegawai tujuan wajib dipilih',
                'potongan_id.required' => 'Potongan wajib dipilih',
            ]);

            $result = $this->potonganPegawaiService->distribusiSelective($validated);

            return response()->json([
                'status' => true,
                'message' => 'Distribusi berhasil',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

    public function getGapokById($nik)
    {
        return response()->json(
            $this->potonganPegawaiService->getGapokById($nik)
        );
    }
}
