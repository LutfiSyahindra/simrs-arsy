<?php

namespace App\Services\mappingData;

use App\Repositories\mappingData\skorPegawaiRepository;
use Illuminate\Support\Facades\DB;

class skorPegawaiService
{
    /**
     * Create a new class instance.
     */
    protected $skorPegawaiRepository;

    public function __construct(skorPegawaiRepository $skorPegawaiRepository)
    {
        $this->skorPegawaiRepository = $skorPegawaiRepository;
    }

    public function skorPegawaiTable()
    {
        return $this->skorPegawaiRepository
            ->getSkorPegawai()
            ->groupBy('nik')
            ->map(function ($items) {
                $first = $items->first();
                $gapok = $first->gapok;
                $total = $items->sum('bobot_skor');

                $status = match ($gapok->stts_kerja ?? '') {
                    'T' => '<span class="badge bg-light text-primary border">Tetap</span>',
                    'FT' => '<span class="badge bg-light text-success border">Kontrak</span>',
                    'PT' => '<span class="badge bg-light text-warning border">Part Time</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };

                return [
                    'id' => $first->nik,
                    'nik' => $first->nik,
                    'nama_pegawai' => $gapok->nama ?? $first->nik,
                    'jabatan' => $gapok->jbtn ?? '-',
                    'status' => $status,
                    'jumlah_skor' => $items->count(),
                    'total_skor' => (int) $total,
                    'total' => '<span class="fw-bold text-primary">' . number_format((int) $total, 0, ',', '.') . '</span>',
                ];
            })
            ->values();
    }

    public function getPegawai()
    {
        return $this->skorPegawaiRepository->getPegawai();
    }

    public function guideSkor()
    {
        return $this->skorPegawaiRepository->getMasterSkor()->map(function ($skor) {
            return [
                'id' => $skor->id,
                'kd_skor' => $skor->kd_skor,
                'jenis' => $skor->jenis,
                'keterangan' => $skor->keterangan,
                'bobot_skor' => (int) $skor->bobot_skor,
            ];
        });
    }

    public function create(string $nik, array $skorIds)
    {
        return DB::transaction(function () use ($nik, $skorIds) {
            $masterSkor = $this->skorPegawaiRepository->findSkorByIds($skorIds);
            $now = now();

            $data = collect($skorIds)
                ->filter(fn ($id) => $masterSkor->has((int) $id))
                ->map(function ($id) use ($nik, $masterSkor, $now) {
                    $skor = $masterSkor[(int) $id];

                    return [
                        'nik' => $nik,
                        'skor_id' => $skor->id,
                        'bobot_skor' => (int) $skor->bobot_skor,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->values()
                ->toArray();

            if (empty($data)) {
                return false;
            }

            return $this->skorPegawaiRepository->insert($data);
        });
    }

    public function existingSkorIds(string $nik, array $skorIds)
    {
        return $this->skorPegawaiRepository->existingSkorIds($nik, $skorIds);
    }

    public function findById($id)
    {
        return $this->skorPegawaiRepository->findById($id);
    }

    public function existsSkorForPegawai(string $nik, int $skorId, $exceptId = null)
    {
        return $this->skorPegawaiRepository->existsSkorForPegawai($nik, $skorId, $exceptId);
    }

    public function update($id, int $skorId)
    {
        $skor = $this->skorPegawaiRepository->findSkorById($skorId);

        if (!$skor) {
            return null;
        }

        return $this->skorPegawaiRepository->update($id, [
            'skor_id' => $skor->id,
            'bobot_skor' => (int) $skor->bobot_skor,
        ]);
    }

    public function getByPegawai(string $nik)
    {
        return $this->skorPegawaiRepository->getByPegawai($nik)->map(function ($item) {
            return [
                'id' => $item->id,
                'skor_id' => $item->skor_id,
                'kd_skor' => $item->skor->kd_skor ?? '-',
                'jenis' => $item->skor->jenis ?? '-',
                'keterangan' => $item->skor->keterangan ?? '-',
                'bobot_skor' => (int) $item->bobot_skor,
            ];
        });
    }

    public function getGapokById(string $nik)
    {
        return $this->skorPegawaiRepository->getGapokById($nik);
    }

    public function delete($id)
    {
        return $this->skorPegawaiRepository->delete($id);
    }
}
