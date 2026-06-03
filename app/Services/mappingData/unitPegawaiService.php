<?php

namespace App\Services\mappingData;

use App\Export\Mapping\unitPegawaiExport;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\unitModel;
use App\Models\dbSimrs\unitPegawaiModel;
use App\Repositories\mappingData\unitPegawaiRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class unitPegawaiService
{
    protected $unitPegawaiRepository;

    public function __construct(unitPegawaiRepository $unitPegawaiRepository)
    {
        $this->unitPegawaiRepository = $unitPegawaiRepository;
    }

    protected $added = 0;
    protected $skipped = 0;

    public function unitPegawaiTable()
    {
        return $this->unitPegawaiRepository
            ->getUnitPegawai()
            ->groupBy('nik')
            ->map(function ($items) {
                $first = $items->first();
                $gapok = $first->gapok;

                $status = match ($gapok->stts_kerja ?? '') {
                    'T' => '<span class="badge bg-light text-primary border">Tetap</span>',
                    'FT' => '<span class="badge bg-light text-success border">Kontrak</span>',
                    'PT' => '<span class="badge bg-light text-warning border">Part Time</span>',
                    'MT' => '<span class="badge bg-light text-danger border">Mitra</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };

                return [
                    'id' => $first->nik,
                    'nik' => $first->nik,
                    'nama_pegawai' => $gapok->nama ?? $first->nik,
                    'jabatan' => $gapok->jbtn ?? '-',
                    'status' => $status,
                    'jumlah_unit' => $items->count(),
                    'total' => '<span class="fw-bold text-primary">' . $items->count() . ' unit</span>',
                ];
            })
            ->values();
    }

    public function getPegawai()
    {
        return $this->unitPegawaiRepository->getPegawai();
    }

    public function guideUnit()
    {
        return $this->unitPegawaiRepository->getMasterUnit()->map(function ($unit) {
            return [
                'id' => $unit->id,
                'kode' => $unit->kode,
                'jenis' => $unit->jenis,
                'keterangan' => $unit->keterangan,
            ];
        });
    }

    public function create(string $nik, array $unitIds)
    {
        return DB::transaction(function () use ($nik, $unitIds) {
            $masterUnit = $this->unitPegawaiRepository->findUnitByIds($unitIds);
            $now = now();

            $data = collect($unitIds)
                ->filter(fn ($id) => $masterUnit->has((int) $id))
                ->map(function ($id) use ($nik, $now) {
                    return [
                        'nik' => $nik,
                        'unit_id' => (int) $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->values()
                ->toArray();

            if (empty($data)) {
                return false;
            }

            return $this->unitPegawaiRepository->insert($data);
        });
    }

    public function createByUnit(int $unitId, array $niks)
    {
        return DB::transaction(function () use ($unitId, $niks) {
            $unit = $this->unitPegawaiRepository->findUnitById($unitId);

            if (!$unit) {
                return false;
            }

            $now = now();
            $data = collect($niks)
                ->filter()
                ->unique()
                ->map(function ($nik) use ($unitId, $now) {
                    return [
                        'nik' => (string) $nik,
                        'unit_id' => $unitId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->values()
                ->toArray();

            if (empty($data)) {
                return false;
            }

            return $this->unitPegawaiRepository->insert($data);
        });
    }

    public function existingUnitIds(string $nik, array $unitIds)
    {
        return $this->unitPegawaiRepository->existingUnitIds($nik, $unitIds);
    }

    public function existingNiksForUnit(int $unitId, array $niks)
    {
        return $this->unitPegawaiRepository->existingNiksForUnit($unitId, $niks);
    }

    public function findById($id)
    {
        return $this->unitPegawaiRepository->findById($id);
    }

    public function existsUnitForPegawai(string $nik, int $unitId, $exceptId = null)
    {
        return $this->unitPegawaiRepository->existsUnitForPegawai($nik, $unitId, $exceptId);
    }

    public function update($id, int $unitId)
    {
        $unit = $this->unitPegawaiRepository->findUnitById($unitId);

        if (!$unit) {
            return null;
        }

        return $this->unitPegawaiRepository->update($id, [
            'unit_id' => $unit->id,
        ]);
    }

    public function getByPegawaiUnit(string $nik)
    {
        return $this->unitPegawaiRepository->getByPegawai($nik)->map(function ($item) {
            return [
                'id' => $item->id,
                'unit_id' => $item->unit_id,
                'kode' => $item->unit->kode ?? '-',
                'jenis' => $item->unit->jenis ?? '-',
                'keterangan' => $item->unit->keterangan ?? '-',
            ];
        });
    }

    public function getGapokById(string $nik)
    {
        return $this->unitPegawaiRepository->getGapokById($nik);
    }

    public function delete($id)
    {
        return $this->unitPegawaiRepository->delete($id);
    }

    public function exportTemplate()
    {
        try {
            $fileName = 'Template_Unit_Pegawai.xlsx';
            return Excel::download(new unitPegawaiExport, $fileName);
        } catch (Exception $e) {
            Log::error('Gagal export template Unit Pegawai: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Gagal membuat template: ' . $e->getMessage(),
            ];
        }
    }

    public function prosesImportUnitPegawai($data)
    {
        $nik = trim($data['nik'] ?? '');
        $kode = strtoupper(trim($data['unit_kode'] ?? ''));
        $kode = preg_replace('/[^A-Z0-9]/', '', $kode);

        if ($kode === '') {
            $this->skipped++;
            return;
        }

        $gapok = gapokModel::where('nik', $nik)->first();
        if (!$gapok) {
            $this->skipped++;
            return;
        }

        $unit = unitModel::where('kode', $kode)->first();
        if (!$unit) {
            $this->skipped++;
            return;
        }

        $sudahAda = unitPegawaiModel::where('nik', $nik)
            ->where('unit_id', $unit->id)
            ->exists();

        if ($sudahAda) {
            $this->skipped++;
            return;
        }

        unitPegawaiModel::create([
            'nik' => $nik,
            'unit_id' => $unit->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->added++;
    }

    public function resetCounter()
    {
        $this->added = 0;
        $this->skipped = 0;
    }

    public function getAdded()
    {
        return $this->added;
    }

    public function getSkipped()
    {
        return $this->skipped;
    }
}
