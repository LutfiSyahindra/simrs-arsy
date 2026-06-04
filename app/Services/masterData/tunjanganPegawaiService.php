<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\tunjanganPegawaiExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jabatanModel;
use App\Models\dbSimrs\jnsTunjanganModel;
use App\Models\dbSimrs\profesiModel;
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
        $jabatanList = $this->tunjanganPegawaiRepository->getJabatan();
        $profesiList = $this->tunjanganPegawaiRepository->getProfesi();

        return $this->tunjanganPegawaiRepository
            ->getTunjanganPegawai()
            ->groupBy('nik')
            ->map(function ($items) use ($jabatanList, $profesiList) {

                $first = $items->first();

                $status = match ($first->gapok->stts_kerja ?? '') {
                    'T' => '<span class="badge bg-light text-primary border">Tetap</span>',
                    'FT' => '<span class="badge bg-light text-success border">Kontrak</span>',
                    'PT' => '<span class="badge bg-light text-warning border">Part Time</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };

                // 🔥 LIST
                $tunjanganList = $items->map(function ($t) use ($jabatanList, $profesiList) {

                    $inputDetail = '-';

                    switch ($t->jenisTunjangan->tipe) {

                        case 'jabatan':
                            $options = collect($jabatanList)->map(function ($j) use ($t) {
                                $selected = $t->referensi_id == $j->id ? 'selected' : '';
                                return "<option value='{$j->id}' {$selected}>{$j->nama}</option>";
                            })->implode('');

                            $inputDetail = "
                                <select class='form-select form-select-sm input-ref'
                                    data-id='{$t->id}'
                                    data-old='{$t->referensi_id}'>
                                    {$options}
                                </select>
                            ";
                            break;

                        case 'profesi':
                            $options = collect($profesiList)->map(function ($p) use ($t) {
                                $selected = $t->referensi_id == $p->id ? 'selected' : '';
                                return "<option value='{$p->id}' {$selected}>{$p->nama}</option>";
                            })->implode('');

                            $inputDetail = "
                                <select class='form-select form-select-sm input-ref'
                                    data-id='{$t->id}'
                                    data-old='{$t->referensi_id}'>
                                    {$options}
                                </select>
                            ";
                            break;

                        case 'anak':
                            $inputDetail = "
                                <div class='d-flex align-items-center gap-2'>
                                    <input type='number'
                                        class='form-control form-control-sm input-qty'
                                        value='".($t->qty ?? 0)."'
                                        data-id='{$t->id}'
                                        data-old='".($t->qty ?? 0)."'
                                        min='0' max='3'
                                        style='width:80px;' />
                                    <span class='text-muted small'>maks 3 anak</span>
                                </div>
                            ";
                            break;

                        case 'pasangan':
                            $inputDetail = "<span class='text-muted small'>1 pasangan</span>";
                            break;

                        case 'masa_kerja':
                            $inputDetail = "<span class='text-muted small'>otomatis berdasarkan masa kerja</span>";
                            break;

                        case 'custom':
                            $inputDetail = "<span class='text-muted small'>otomatis dari master tunjangan</span>";
                            break;

                        case 'manual':
                            $inputDetail = "<span class='text-muted small'>nominal manual</span>";
                            break;
                    }

                    $nominalDisabled = $t->jenisTunjangan->tipe === 'manual'
                        ? ''
                        : 'disabled';

                    return "
                        <div class='list-group-item py-3' data-tunjangan-id='{$t->tunjangan_id}'>

                            <div class='row align-items-center g-2'>

                                <!-- LEFT -->
                                <div class='col-md-7'>

                                    <div class='d-flex align-items-center gap-2 mb-1'>
                                        <span class='badge bg-light text-primary border'>
                                            {$t->jenisTunjangan->kode}
                                        </span>

                                        <span class='fw-semibold'>
                                            {$t->jenisTunjangan->nama}
                                        </span>
                                    </div>

                                    {$inputDetail}

                                </div>

                                <!-- RIGHT -->
                                <div class='col-md-5'>

                                    <div class='d-flex justify-content-end align-items-center gap-2'>

                                        <div class='text-end'>
                                            <small class='text-muted d-block'>Nominal</small>

                                            <input type='number'
                                                class='form-control form-control-sm text-end fw-semibold input-nominal'
                                                value='{$t->nominal}'
                                                data-id='{$t->id}'
                                                data-old='{$t->nominal}'
                                                style='width:140px;' {$nominalDisabled} />
                                        </div>

                                        <button class='btn btn-success btn-sm btn-save'
                                            data-id='{$t->id}'
                                            disabled>
                                            <i class='mdi mdi-content-save'></i>
                                        </button>

                                        <button class='btn btn-danger btn-sm btn-delete'
                                            data-id='{$t->id}'>
                                            <i class='mdi mdi-trash-can'></i>
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>
                    ";
                })->implode('');

                $total = $items->sum('nominal');

                return [
                    'id' => $first->nik,
                    'nik' => $first->nik,
                    'nama' => $first->gapok->nama ?? '-',
                    'jabatan' => '<span class="fw-semibold">' . ($first->gapok->jbtn ?? '-') . '</span>',
                    'status' => $status,
                    'tunjangan_ids' => $items->pluck('tunjangan_id')->values()->all(),

                    // 🔥 FIX DI SINI (GABUNG HEADER + LIST)
                    'tunjangan' => "
                        <div class='list-group list-group-flush tunjangan-detail-list'>
                            {$tunjanganList}
                        </div>
                    ",

                    'total' => '<span class="fw-bold text-primary">'
                        . number_format($total, 0, ',', '.')
                        . '</span>'
                ];
            })
            ->values();
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
        Log::info('Memproses data: ' . json_encode($data));
        $nik = trim($data['nik'] ?? '');
        $kode = strtoupper(trim($data['tunjangan_id'] ?? ''));
        $refId = $data['referensi_id'] ?? null;
        $qty = is_numeric($data['qty']) ? $data['qty'] : 1;

        $kode = preg_replace('/[^A-Z0-9]/', '', $kode);

        if ($kode === '') {
            $this->skipped++;
            return;
        }

        // CEK PEGAWAI
        $gapok = gapokModel::where('nik', $nik)->first();
        if (!$gapok) {
            $this->skipped++;
            return;
        }

        // CEK MASTER TUNJANGAN
        $tunjangan = jnsTunjanganModel::where('kode', $kode)->first();
        if (!$tunjangan) {
            $this->skipped++;
            return;
        }

        $nominal = 0;
        $shouldRound = true;

        if ($tunjangan->tipe === 'custom') {
            $nominal = $tunjangan->nilai ?? 0;
            $refId = null;
            $qty = null;
            $shouldRound = false;
        } else {
            switch ($kode) {

            case 'TJ001':

                if (!$refId) {
                    $this->skipped++;
                    return;
                }

                $jabatan = jabatanModel::where('kode', $refId)->first();

                if (!$jabatan) {
                    $this->skipped++;
                    return;
                }

                $nominal = $jabatan->tunjangan ?? 0;

                // ⬇️ INI YANG PENTING
                $refId = $jabatan->id;

                $nominal = $jabatan->tunjangan ?? 0;
                break;


            case 'TJ005':

                if (!$refId) {
                    $this->skipped++;
                    return;
                }

                $profesi = profesiModel::where('kode', $refId)->first();

                if (!$profesi) {
                    $this->skipped++;
                    return;
                }

                $nominal = $profesi->tunjangan ?? 0;

                // ⬇️ INI YANG PENTING
                $refId = $profesi->id;

                $nominal = $profesi->tunjangan ?? 0;
                break;

            // ======================
            // TJ002 → ANAK
            // qty * nilai% * gaji
            // ======================
            case 'TJ002':

                $nilai = $tunjangan->nilai ?? 0;

                $nominal = $qty * ($nilai / 100) * $gapok->gaji_pokok;
                break;

            // ======================
            // TJ003 → PASANGAN
            // nilai% * gaji
            // ======================
            case 'TJ003':

                $nilai = $tunjangan->nilai ?? 0;

                $nominal = ($nilai / 100) * $gapok->gaji_pokok;
                break;

            // ======================
            // TJ004 → MASA KERJA
            // ======================
            case 'TJ004':

                $nilai = $tunjangan->nilai ?? 0;

                // 🔥 PARSING "12 Tahun 2 Bulan"
                preg_match('/(\d+)/', $gapok->masa_kerja, $match);

                $tahun = $match[0] ?? 0;

                $nominal = $tahun * $nilai;
                break;

            default:
                $this->skipped++;
                return;
            }
        }

        if ($shouldRound) {
            $nominal = round($nominal, -3);
        }

        // SIMPAN
        tunjanganPegawaiModel::updateOrCreate(
            [
                'nik' => $nik,
                'tunjangan_id' => $tunjangan->id,
                'referensi_id' => $refId // 🔥 tambahkan ini
            ],
            [
                'qty' => $qty,
                'nominal' => $nominal,
                'updated_at' => now()
            ]
        );

        $this->added++;
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
        DB::beginTransaction();

        try {

            $inserted = [];

            foreach ($data as $row) {
                $tunjangan = jnsTunjanganModel::find($row['tunjangan_id']);

                if ($tunjangan && $tunjangan->tipe === 'custom') {
                    $row['referensi_id'] = null;
                    $row['qty'] = null;
                    $row['nominal'] = $tunjangan->nilai ?? 0;
                }

                $inserted[] = DB::table('tunjangan_pegawai')->insertGetId([
                    'nik' => $row['nik'],
                    'tunjangan_id' => $row['tunjangan_id'],
                    'referensi_id' => $row['referensi_id'],
                    'qty' => $row['qty'],
                    'nominal' => $row['nominal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return $inserted;

        } catch (\Throwable $e) {

            DB::rollBack();
            throw $e;
        }
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

                $master = jnsTunjanganModel::find($t->tunjangan_id);

                if (!$master) {
                    $skipped++;
                    continue;
                }

                if ($master->tipe === 'custom') {
                    $nominal = $master->nilai ?? 0;
                } elseif ($master->tipe === 'manual') {
                    $nominal = $t->nominal ?? 0;
                } else {
                    $persen = $master->nilai ?? 0;
                    $nominal = round($gapok->gaji_pokok * ($persen / 100), -3);
                }

                tunjanganPegawaiModel::create([
                    'nik' => $nikTujuan,
                    'tunjangan_id' => $t->tunjangan_id,
                    'referensi_id' => in_array($master->tipe, ['jabatan', 'profesi']) ? $t->referensi_id : null,
                    'qty' => $master->tipe === 'anak' ? $t->qty : null,
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

    public function getListJabatan()
    {
        return $this->tunjanganPegawaiRepository->getJabatan();
    }

    public function getGapokById($nik){
        return $this->tunjanganPegawaiRepository->getGapokById($nik);
    }

    public function getListProfesi()
    {
        return $this->tunjanganPegawaiRepository->getProfesi();
    }

    public function updateInline($id, $data)
    {
        return DB::table('tunjangan_pegawai')
            ->where('id', $id)
            ->update($data);
    }

    public function findById($id)
    {
        return tunjanganPegawaiModel::with(['jenisTunjangan', 'gapok'])
            ->find($id);
    }

    public function getJabatanById($id)
    {
        return jabatanModel::find($id);
    }

    public function getProfesiById($id)
    {
        return profesiModel::find($id);
    }
}
