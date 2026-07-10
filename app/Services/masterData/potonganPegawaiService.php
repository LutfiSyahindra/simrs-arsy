<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\potonganPegawaiExport;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsPotonganModel;
use App\Models\dbSimrs\potonganPegawaiModel;
use App\Repositories\masterData\jenisPotonganRepository;
use App\Repositories\masterData\masterGapokRepository;
use App\Repositories\masterData\potonganPegawaiRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class potonganPegawaiService
{
    protected $potonganPegawaiRepository;

    protected $jenisPotonganRepository;

    protected $masterGapokRepository;

    protected $added = 0;

    protected $skipped = 0;

    public function __construct(
        potonganPegawaiRepository $potonganPegawaiRepository,
        jenisPotonganRepository $jenisPotonganRepository,
        masterGapokRepository $masterGapokRepository
    ) {
        $this->potonganPegawaiRepository = $potonganPegawaiRepository;
        $this->jenisPotonganRepository = $jenisPotonganRepository;
        $this->masterGapokRepository = $masterGapokRepository;
    }

    public function getPegawai()
    {
        return $this->masterGapokRepository->getGapok();
    }

    public function getPotonganPegawaiTable()
    {
        return $this->potonganPegawaiRepository
            ->getPotonganPegawai()
            ->groupBy('nik')
            ->map(function ($items) {
                $first = $items->first();

                $status = match ($first->gapok->stts_kerja ?? '') {
                    'T' => '<span class="badge bg-light text-primary border">Tetap</span>',
                    'FT' => '<span class="badge bg-light text-success border">Kontrak</span>',
                    'PT' => '<span class="badge bg-light text-warning border">Part Time</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };

                $potonganList = $items->map(function ($p) {
                    $jenis = $p->jenisPotongan;
                    $tipe = $jenis->tipe ?? 'manual';
                    $inputDetail = match ($tipe) {
                        'nominal' => "<span class='text-muted small'>nominal tetap dari master potongan</span>",
                        'persen_gapok' => "<span class='text-muted small'>".rtrim(rtrim((string) ($jenis->nilai ?? 0), '0'), '.').'% x gaji pokok</span>',
                        'persen_total_gaji' => "<span class='text-muted small'>".rtrim(rtrim((string) ($jenis->nilai ?? 0), '0'), '.').'% x gaji tahap 1 + 2, dihitung saat generate tahap 2</span>',
                        default => "<span class='text-muted small'>nominal manual per pegawai</span>",
                    };

                    $nominalDisabled = $tipe === 'manual' ? '' : 'disabled';

                    return "
                        <div class='list-group-item py-3' data-potongan-id='{$p->potongan_id}'>
                            <div class='row align-items-center g-2'>
                                <div class='col-md-7'>
                                    <div class='d-flex align-items-center gap-2 mb-1'>
                                        <span class='badge bg-light text-danger border'>
                                            {$jenis->kode}
                                        </span>
                                        <span class='fw-semibold'>
                                            {$jenis->nama}
                                        </span>
                                    </div>
                                    {$inputDetail}
                                </div>

                                <div class='col-md-5'>
                                    <div class='d-flex justify-content-end align-items-center gap-2'>
                                        <div class='text-end'>
                                            <small class='text-muted d-block'>Nominal</small>
                                            <input type='number'
                                                class='form-control form-control-sm text-end fw-semibold input-nominal'
                                                value='{$p->nominal}'
                                                data-id='{$p->id}'
                                                data-old='{$p->nominal}'
                                                style='width:140px;' {$nominalDisabled} />
                                        </div>

                                        <button class='btn btn-success btn-sm btn-save'
                                            data-id='{$p->id}'
                                            disabled>
                                            <i class='mdi mdi-content-save'></i>
                                        </button>

                                        <button class='btn btn-danger btn-sm btn-delete'
                                            data-id='{$p->id}'>
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
                    'jabatan' => '<span class="fw-semibold">'.($first->gapok->jbtn ?? '-').'</span>',
                    'status' => $status,
                    'potongan_ids' => $items->pluck('potongan_id')->values()->all(),
                    'potongan' => "
                        <div class='list-group list-group-flush potongan-detail-list'>
                            {$potonganList}
                        </div>
                    ",
                    'total' => '<span class="fw-bold text-danger">'
                        .number_format($total, 0, ',', '.')
                        .'</span>',
                ];
            })
            ->values();
    }

    public function guideJenisPotongan()
    {
        return $this->jenisPotonganRepository->getJnsPotongan();
    }

    public function exportTemplate()
    {
        try {
            return Excel::download(new potonganPegawaiExport, 'Template_Potongan_Pegawai.xlsx');
        } catch (Exception $e) {
            Log::error('Gagal export template Potongan Pegawai: '.$e->getMessage());

            return [
                'status' => false,
                'message' => 'Gagal membuat template: '.$e->getMessage(),
            ];
        }
    }

    public function resetCounter()
    {
        $this->added = 0;
        $this->skipped = 0;
    }

    public function prosesImportPotonganPegawai($data)
    {
        $nik = trim((string) ($data['nik'] ?? ''));
        $kode = strtoupper(trim((string) ($data['potongan_id'] ?? '')));
        $nominalManual = $data['nominal'] ?? null;

        $kode = preg_replace('/[^A-Z0-9]/', '', $kode);

        if ($nik === '' || $kode === '') {
            $this->skipped++;

            return;
        }

        $gapok = gapokModel::where('nik', $nik)->first();
        if (! $gapok) {
            $this->skipped++;

            return;
        }

        $potongan = jnsPotonganModel::where('kode', $kode)->first();
        if (! $potongan) {
            $this->skipped++;

            return;
        }

        if ($potongan->tipe === 'manual' && ! is_numeric($nominalManual)) {
            $this->skipped++;

            return;
        }

        $nominal = $this->calculateNominal($potongan, $gapok, $nominalManual);

        potonganPegawaiModel::updateOrCreate(
            [
                'nik' => $nik,
                'potongan_id' => $potongan->id,
            ],
            [
                'nominal' => $nominal,
                'updated_at' => now(),
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
        return DB::transaction(function () use ($data) {
            $inserted = [];

            foreach ($data as $row) {
                $potongan = jnsPotonganModel::findOrFail($row['potongan_id']);
                $gapok = gapokModel::where('nik', $row['nik'])->first();

                $inserted[] = DB::table('potongan_pegawai')->insertGetId([
                    'nik' => $row['nik'],
                    'potongan_id' => $row['potongan_id'],
                    'nominal' => $this->calculateNominal($potongan, $gapok, $row['nominal'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $inserted;
        });
    }

    public function updateNominal($id, $nominal)
    {
        return $this->potonganPegawaiRepository->updateNominal($id, $nominal);
    }

    public function bulkUpdate(array $data)
    {
        $updates = [];

        foreach ($data as $id => $nominal) {
            if (! is_numeric($nominal)) {
                continue;
            }

            $updates[] = [
                'id' => $id,
                'nominal' => $nominal,
                'updated_at' => now(),
            ];
        }

        return $this->potonganPegawaiRepository->bulkUpdate($updates);
    }

    public function delete($id)
    {
        return $this->potonganPegawaiRepository->delete($id);
    }

    public function getByPegawai($nik)
    {
        return $this->potonganPegawaiRepository
            ->getByPegawai($nik)
            ->map(function ($p) {
                return [
                    'potongan_id' => $p->potongan_id,
                    'nama' => $p->jenisPotongan->nama ?? '-',
                ];
            });
    }

    public function distribusiSelective($data)
    {
        $sumber = $data['sumber'];
        $tujuanList = $data['tujuan'];
        $potonganIds = $data['potongan_id'];

        $added = 0;
        $skipped = 0;

        $potonganSumber = potonganPegawaiModel::where('nik', $sumber)
            ->whereIn('potongan_id', $potonganIds)
            ->get();

        foreach ($tujuanList as $nikTujuan) {
            $gapok = gapokModel::where('nik', $nikTujuan)->first();

            if (! $gapok) {
                continue;
            }

            foreach ($potonganSumber as $p) {
                $exists = potonganPegawaiModel::where('nik', $nikTujuan)
                    ->where('potongan_id', $p->potongan_id)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $master = jnsPotonganModel::find($p->potongan_id);
                if (! $master) {
                    $skipped++;

                    continue;
                }

                potonganPegawaiModel::create([
                    'nik' => $nikTujuan,
                    'potongan_id' => $p->potongan_id,
                    'nominal' => $this->calculateNominal($master, $gapok, $p->nominal),
                ]);

                $added++;
            }
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
        ];
    }

    public function getGapokById($nik)
    {
        $gapok = $this->potonganPegawaiRepository->getGapokById($nik);

        return $gapok;
    }

    public function updateInline($id, $data)
    {
        return DB::table('potongan_pegawai')
            ->where('id', $id)
            ->update($data);
    }

    public function findById($id)
    {
        return potonganPegawaiModel::with(['jenisPotongan', 'gapok'])
            ->find($id);
    }

    public function calculateNominal($potongan, $gapok, $manualNominal = null): int
    {
        $tipe = $potongan->tipe ?? 'manual';
        $nilai = (float) ($potongan->nilai ?? 0);
        $gajiPokok = (float) ($gapok->gaji_pokok ?? 0);

        return match ($tipe) {
            'nominal' => (int) round($nilai),
            'persen_gapok' => (int) round($gajiPokok * ($nilai / 100)),
            'persen_total_gaji' => 0,
            default => (int) round((float) ($manualNominal ?? 0)),
        };
    }
}
