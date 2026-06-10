<?php

namespace App\Services\mappingData;

use App\Repositories\mappingData\premiMappingRepository;
use Illuminate\Support\Facades\DB;

class premiMappingService
{
    protected $premiMappingRepository;

    public function __construct(premiMappingRepository $premiMappingRepository)
    {
        $this->premiMappingRepository = $premiMappingRepository;
    }

    public function premiTable()
    {
        return $this->premiMappingRepository
            ->getPremiWithCounts()
            ->map(function ($premi) {
                $jumlah = (int) $premi->jumlah_tindakan;

                return [
                    'id' => $premi->id,
                    'kode' => $premi->kode,
                    'jenis' => $premi->jenis,
                    'jumlah_tindakan' => $jumlah,
                    'total' => '<span class="fw-bold text-primary">'.$jumlah.' tindakan</span>',
                ];
            });
    }

    public function guideJenisTindakan()
    {
        return $this->premiMappingRepository->getMasterJnsTindakan()->map(function ($jenis) {
            return [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'jenis' => $jenis->jenis,
                'text' => trim($jenis->kode.' - '.$jenis->jenis),
            ];
        });
    }

    public function guideJenisPremi()
    {
        return $this->premiMappingRepository->getMasterJnsPremi()->map(function ($premi) {
            return [
                'id' => $premi->id,
                'kode' => $premi->kode,
                'jenis' => $premi->jenis,
            ];
        });
    }

    public function findPremiById(int $id)
    {
        return $this->premiMappingRepository->findPremiById($id);
    }

    public function existingTindakanIds(int $premiId, array $tindakanIds)
    {
        return $this->premiMappingRepository->existingTindakanIds($premiId, $tindakanIds);
    }

    public function create(int $premiId, array $mappings)
    {
        return DB::transaction(function () use ($premiId, $mappings) {
            $tindakanIds = collect($mappings)->pluck('jnsTindakan_id')->map(fn ($id) => (int) $id)->all();
            $masterTindakan = $this->premiMappingRepository->findTindakanByIds($tindakanIds);
            $now = now();

            $data = collect($mappings)
                ->filter(fn ($mapping) => $masterTindakan->has((int) $mapping['jnsTindakan_id']))
                ->map(function ($mapping) use ($premiId, $now) {
                    return [
                        'jnsPremi_id' => $premiId,
                        'jnsTindakan_id' => (int) $mapping['jnsTindakan_id'],
                        'nilai' => (int) $mapping['nilai'],
                        'jenis' => $mapping['jenis'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->values()
                ->toArray();

            return empty($data) ? false : $this->premiMappingRepository->insert($data);
        });
    }

    public function findById($id)
    {
        $row = $this->premiMappingRepository->findById($id);

        return $row ? $this->formatMapping($row) : null;
    }

    public function getByPremi(int $premiId)
    {
        return $this->premiMappingRepository
            ->getMappingsByPremi($premiId)
            ->map(fn ($item) => $this->formatMapping($item));
    }

    public function existsMapping(int $premiId, int $tindakanId, $exceptId = null)
    {
        return $this->premiMappingRepository->existsMapping($premiId, $tindakanId, $exceptId);
    }

    public function update($id, int $tindakanId, string $jenis, int $nilai)
    {
        return $this->premiMappingRepository->update($id, [
            'jnsTindakan_id' => $tindakanId,
            'jenis' => $jenis,
            'nilai' => $nilai,
        ]);
    }

    public function delete($id)
    {
        return $this->premiMappingRepository->delete($id);
    }

    protected function formatMapping($item)
    {
        return [
            'id' => $item->id,
            'jnsPremi_id' => (int) $item->jnsPremi_id,
            'jnsTindakan_id' => (int) $item->jnsTindakan_id,
            'kode_tindakan' => $item->jnsTindakan->kode ?? '-',
            'jenis_tindakan' => $item->jnsTindakan->jenis ?? '-',
            'jenis' => $item->jenis,
            'nilai' => (int) $item->nilai,
        ];
    }
}
