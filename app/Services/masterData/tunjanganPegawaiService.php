<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\tunjanganPegawaiExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsTunjanganModel;
use App\Models\dbSimrs\tunjanganPegawaiModel;
use App\Repositories\masterData\jenisTunjanganRepository;
use App\Repositories\masterData\masterGapokRepository;
use App\Repositories\masterData\tunjanganPegawaiRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class tunjanganPegawaiService
{
    protected $tunjanganPegawaiRepository, $jenisTunjanganRepository, $masterGapokRepository;

    public function __construct(tunjanganPegawaiRepository $tunjanganPegawaiRepository, jenisTunjanganRepository $jenisTunjanganRepository, masterGapokRepository $masterGapokRepository)
    {
        $this->tunjanganPegawaiRepository = $tunjanganPegawaiRepository;
        $this->jenisTunjanganRepository = $jenisTunjanganRepository;
        $this->masterGapokRepository = $masterGapokRepository;
    }

    protected $added = 0;
    protected $skipped = 0;

    public function getPegawai()
    {
        return $this->masterGapokRepository->getGapok();
    }

    public function getTunjanganPegawaiTable()
    {
        return $this->tunjanganPegawaiRepository
            ->getTunjanganPegawai()
            ->groupBy('nik')
            ->map(function ($items) {

                $first = $items->first();

                $status = match ($first->gapok->stts_kerja ?? '') {
                    'T' => '<span class="badge bg-light text-primary border">Tetap</span>',
                    'FT' => '<span class="badge bg-light text-success border">Kontrak</span>',
                    'PT' => '<span class="badge bg-light text-warning border">Part Time</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };

                $tunjanganList = $items->map(function ($t) {
                    return '
                        <div class="list-group-item d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-primary border">
                                    '.$t->jenisTunjangan->kode.'
                                </span>
                                <span>'.$t->jenisTunjangan->nama.'</span>
                            </div>

                            <div class="d-flex align-items-center gap-2">

                                <input type="number"
                                    class="form-control form-control-sm input-nominal"
                                    value="'.$t->nominal.'"
                                    data-id="'.$t->id.'"
                                    data-old="'.$t->nominal.'"
                                    style="width:120px;" />

                                <button class="btn btn-sm btn-success btn-save"
                                    data-id="'.$t->id.'">
                                    <i class="mdi mdi-check"></i>
                                </button>

                                <button class="btn btn-sm btn-danger btn-delete"
                                    data-id="'.$t->id.'">
                                    <i class="mdi mdi-trash-can"></i>
                                </button>

                            </div>

                        </div>
                    ';
                })->implode('');

                $total = $items->sum('nominal');

                return [
                    'id' => $first->nik, // 🔥 penting untuk action
                    'nama' => $first->gapok->nama ?? '-',
                    'jabatan' => '<span class="fw-semibold">' . ($first->gapok->jbtn ?? '-') . '</span>',
                    'status' => $status,
                    'tunjangan' => $tunjanganList,
                    'total' => '<span class="fw-bold text-primary">'
                                .number_format($total,0,',','.')
                            .'</span>'
                ];
            })
            ->values(); // 🔥 WAJIB biar index rapi
    }

    public function guideJenisTunjangan()
    {
        return $this->jenisTunjanganRepository->getJnsTunjangan();
    }

    public function exportTemplate()
    {
        try {
            $fileName = 'Template_Tunjangan_Pegawai.xlsx';

            // Bisa langsung dikembalikan ke controller
            return Excel::download(new tunjanganPegawaiExport, $fileName);
        } catch (Exception $e) {
            Log::error('Gagal export template Tunjangan Pegawai: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Gagal membuat template: ' . $e->getMessage(),
            ];
        }
    }

    public function resetCounter()
    {
        $this->added = 0;
        $this->skipped = 0;
    }

    public function prosesImportTunjanganPegawai($data)
    {
        // NORMALISASI
        $nik = trim($data['nik'] ?? '');
        $kode = strtoupper(trim($data['tunjangan_id'] ?? ''));

        $kode = preg_replace('/[^A-Z0-9]/', '', $kode);

        if ($kode === '') {
            $this->skipped++;
            return;
        }

        // CEK PEGAWAI (GAPOK)
        $gapok = gapokModel::where('nik', $nik)->first();

        if (!$gapok) {
            $this->skipped++;
            return;
        }

        // MAPPING KODE → TUNJANGAN
        $tunjangan = jnsTunjanganModel::whereRaw(
            'UPPER(REPLACE(kode," ","")) = ?',
            [$kode]
        )->first();

        if (!$tunjangan) {
            $this->skipped++;
            return;
        }

        // HITUNG NOMINAL 🔥
        $persen = $tunjangan->persentase ?? 0;

        if ($persen <= 0) {
            $this->skipped++;
            return;
        }

        $nominal = round($gapok->gaji_pokok * ($persen / 100), -3);

        // CEK EXISTING
        $exists = tunjanganPegawaiModel::where('nik', $nik)
            ->where('tunjangan_id', $tunjangan->id)
            ->exists();

        // SIMPAN
        tunjanganPegawaiModel::updateOrCreate(
            [
                'nik' => $nik,
                'tunjangan_id' => $tunjangan->id
            ],
            [
                'nominal' => $nominal,
                'updated_at' => now()
            ]
        );

        // COUNTER
        if ($exists) {
            $this->skipped++;
        } else {
            $this->added++;
        }
    }

    public function getAdded()
    {
        return $this->added;
    }

    public function getSkipped()
    {
        return $this->skipped;
    }


    public function create(array $data)
    {
        $result = [];

        $nik = $data['nik'];

        // =========================
        // AMBIL GAPOK
        // =========================
        $gapok = gapokModel::where('nik', $nik)->first();

        if (!$gapok) {
            throw new \Exception('Data gapok tidak ditemukan');
        }

        foreach ($data['tunjangan_id'] as $tunjanganId) {

            // =========================
            // AMBIL MASTER TUNJANGAN
            // =========================
            $master = jnsTunjanganModel::find($tunjanganId);

            if (!$master) continue;

            $persen = $master->persentase ?? 0;

            // =========================
            // HITUNG NOMINAL OTOMATIS 🔥
            // =========================
            $nominal = round($gapok->gaji_pokok * ($persen / 100), -3);

            // =========================
            // CEK EXISTING
            // =========================
            $exists = $this->tunjanganPegawaiRepository
                ->exists($nik, $tunjanganId);

            // =========================
            // SIMPAN
            // =========================
            $this->tunjanganPegawaiRepository->updateOrCreate(
                [
                    'nik' => $nik,
                    'tunjangan_id' => $tunjanganId
                ],
                [
                    'nominal' => $nominal
                ]
            );

            // =========================
            // RESULT
            // =========================
            $result[] = [
                'tunjangan_id' => $tunjanganId,
                'persentase' => $persen,
                'nominal' => $nominal,
                'status' => $exists ? 'updated' : 'created'
            ];
        }

        return $result;
    }

    public function updateNominal($id, $nominal)
    {
        return $this->tunjanganPegawaiRepository->updateNominal($id, $nominal);
    }

    public function bulkUpdate(array $data)
    {
        $updates = [];

        foreach ($data as $id => $nominal) {
            if (!is_numeric($nominal)) continue;

            $updates[] = [
                'id' => $id,
                'nominal' => $nominal,
                'updated_at' => now()
            ];
        }

        return $this->tunjanganPegawaiRepository->bulkUpdate($updates);
    }

    public function delete($id)
    {
        return $this->tunjanganPegawaiRepository->delete($id);
    }

    public function getByPegawai($nik)
    {
        return $this->tunjanganPegawaiRepository
            ->getByPegawai($nik)
            ->map(function ($t) {
                return [
                    'tunjangan_id' => $t->tunjangan_id,
                    'nama' => $t->jenisTunjangan->nama ?? '-'
                ];
            });
    }

    public function distribusiSelective($data)
    {
        $sumber = $data['sumber'];
        $tujuanList = $data['tujuan'];
        $tunjanganIds = $data['tunjangan_id'];

        $added = 0;
        $skipped = 0;

        $tunjanganSumber = tunjanganPegawaiModel::where('nik', $sumber)
            ->whereIn('tunjangan_id', $tunjanganIds)
            ->get();

        foreach ($tujuanList as $nikTujuan) {

            $gapok = gapokModel::where('nik', $nikTujuan)->first();

            if (!$gapok) continue;

            foreach ($tunjanganSumber as $t) {

                $exists = tunjanganPegawaiModel::where('nik', $nikTujuan)
                    ->where('tunjangan_id', $t->tunjangan_id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // ambil persen terbaru
                $master = jnsTunjanganModel::find($t->tunjangan_id);
                $persen = $master->persentase ?? 0;

                $nominal = round($gapok->gaji_pokok * ($persen / 100), -3);

                tunjanganPegawaiModel::create([
                    'nik' => $nikTujuan,
                    'tunjangan_id' => $t->tunjangan_id,
                    'nominal' => $nominal
                ]);

                $added++;
            }
        }

        return [
            'added' => $added,
            'skipped' => $skipped
        ];
    }
}
