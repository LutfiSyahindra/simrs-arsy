<?php

namespace App\Services\mappingData;

use App\Repositories\mappingData\tindakanMappingRepository;
use Illuminate\Support\Facades\DB;

class tindakanMappingService
{
    protected $tindakanMappingRepository;

    public function __construct(tindakanMappingRepository $tindakanMappingRepository)
    {
        $this->tindakanMappingRepository = $tindakanMappingRepository;
    }

    public function tindakanTable()
    {
        return $this->tindakanMappingRepository
            ->getJenisTindakanWithCounts()
            ->map(function ($jenis) {
                $count = (int) $jenis->jumlah_tindakan;

                return [
                    'id' => $jenis->id,
                    'kode' => $jenis->kode,
                    'jenis' => $jenis->jenis,
                    'jumlah_tindakan' => $count,
                    'total' => '<span class="fw-bold text-primary">' . $count . ' tindakan</span>',
                ];
            });
    }

    public function guideJenisTindakan()
    {
        return $this->tindakanMappingRepository->getMasterJnsTindakan()->map(function ($jenis) {
            return [
                'id' => $jenis->id,
                'kode' => $jenis->kode,
                'jenis' => $jenis->jenis,
            ];
        });
    }

    public function sourceOptions()
    {
        return collect(tindakanMappingRepository::SEARCHABLE_SOURCES)
            ->map(fn ($source) => [
                'id' => $source,
                'text' => $this->tindakanMappingRepository->sourceLabel($source),
            ])
            ->values();
    }

    public function searchTindakan(string $keyword, ?string $source = null)
    {
        return $this->tindakanMappingRepository->searchTindakan($keyword, $source)->map(function ($item) {
            $formatted = $this->formatSourceItem($item);

            return array_merge($formatted, [
                'id' => $formatted['source_key'],
                'text' => $formatted['display_text'],
            ]);
        });
    }

    public function findTindakanByKeys(array $sourceKeys)
    {
        return $this->tindakanMappingRepository->findTindakanByKeys($sourceKeys);
    }

    public function existingSourceKeys(int $jenisId, array $sourceKeys)
    {
        $existing = $this->tindakanMappingRepository
            ->getMappingsByJenis($jenisId)
            ->map(fn ($item) => $this->sourceKeyFromMapping($item))
            ->values();

        return collect($sourceKeys)
            ->filter(fn ($sourceKey) => $existing->contains($sourceKey))
            ->values()
            ->toArray();
    }

    public function create(int $jenisId, array $sourceKeys)
    {
        return DB::transaction(function () use ($jenisId, $sourceKeys) {
            $items = $this->findTindakanByKeys($sourceKeys);
            $now = now();

            $data = collect($sourceKeys)
                ->unique()
                ->map(function ($sourceKey) use ($items, $jenisId, $now) {
                    $item = $items->get($sourceKey);

                    if (!$item) {
                        return null;
                    }

                    return $this->mappingPayload($jenisId, $item, $now);
                })
                ->filter()
                ->values()
                ->toArray();

            if (empty($data)) {
                return false;
            }

            return $this->tindakanMappingRepository->insert($data);
        });
    }

    public function findById($id)
    {
        $row = $this->tindakanMappingRepository->findById($id);

        if (!$row) {
            return null;
        }

        return $this->formatMappingItem($row);
    }

    public function getByJenisTindakan($jenisId)
    {
        return $this->tindakanMappingRepository
            ->getMappingsByJenis($jenisId)
            ->map(fn ($item) => $this->formatMappingItem($item));
    }

    public function existsMapping(int $jenisId, array $sourceItem, $exceptId = null)
    {
        return $this->tindakanMappingRepository->existsMapping(
            $jenisId,
            $sourceItem['sumber_tindakan'],
            $sourceItem['kd_tindakan'],
            $exceptId
        );
    }

    public function update($id, array $sourceItem)
    {
        $row = $this->tindakanMappingRepository->findById($id);

        if (!$row) {
            return null;
        }

        return $this->tindakanMappingRepository->update($id, $this->mappingPayload($row->jnsTindakan_id, $sourceItem));
    }

    public function copyToJenis(int $targetJenisId, array $mappingIds)
    {
        return DB::transaction(function () use ($targetJenisId, $mappingIds) {
            $uniqueIds = collect($mappingIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $rows = $this->tindakanMappingRepository
                ->findByIds($uniqueIds->toArray())
                ->keyBy('id');

            $existingKeys = $this->tindakanMappingRepository
                ->getMappingsByJenis($targetJenisId)
                ->map(fn ($item) => $this->sourceKeyFromMapping($item))
                ->flip()
                ->all();
            $preparedKeys = [];
            $now = now();

            $data = $uniqueIds
                ->map(function ($id) use ($rows, $targetJenisId, $existingKeys, &$preparedKeys, $now) {
                    $row = $rows->get($id);

                    if (!$row) {
                        return null;
                    }

                    $sourceKey = $this->sourceKeyFromMapping($row);

                    if (isset($existingKeys[$sourceKey]) || isset($preparedKeys[$sourceKey])) {
                        return null;
                    }

                    $preparedKeys[$sourceKey] = true;

                    return $this->mappingPayloadFromMapping($targetJenisId, $row, $now);
                })
                ->filter()
                ->values()
                ->toArray();

            if (!empty($data)) {
                $this->tindakanMappingRepository->insert($data);
            }

            return [
                'requested' => $uniqueIds->count(),
                'found' => $rows->count(),
                'copied' => count($data),
                'skipped' => max(0, $uniqueIds->count() - count($data)),
            ];
        });
    }

    public function delete($id)
    {
        return $this->tindakanMappingRepository->delete($id);
    }

    public function deleteSelected(int $jenisId, array $mappingIds)
    {
        $ids = collect($mappingIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($ids)) {
            return 0;
        }

        return $this->tindakanMappingRepository->deleteByIdsAndJenis($ids, $jenisId);
    }

    public function deleteAllByJenis(int $jenisId)
    {
        return $this->tindakanMappingRepository->deleteByJenis($jenisId);
    }

    public function sourceLabel(string $source)
    {
        return $this->tindakanMappingRepository->sourceLabel($source);
    }

    protected function mappingPayload(int $jenisId, array $sourceItem, $timestamp = null)
    {
        $payload = [
            'jnsTindakan_id' => $jenisId,
            'sumber_tindakan' => $sourceItem['sumber_tindakan'],
            'kd_tindakan' => $sourceItem['kd_tindakan'],
            'nm_tindakan' => $sourceItem['nm_tindakan'],
            'kd_pj' => $sourceItem['kd_pj'],
            'nm_pj' => $sourceItem['nm_pj'],
            'parent_kd_tindakan' => $sourceItem['parent_kd_tindakan'],
            'parent_nm_tindakan' => $sourceItem['parent_nm_tindakan'],
            'updated_at' => $timestamp ?: now(),
        ];

        if ($timestamp) {
            $payload['created_at'] = $timestamp;
        }

        return $payload;
    }

    protected function mappingPayloadFromMapping(int $jenisId, $mapping, $timestamp)
    {
        return [
            'jnsTindakan_id' => $jenisId,
            'sumber_tindakan' => $mapping->sumber_tindakan ?: 'LEGACY',
            'kd_tindakan' => $mapping->kd_tindakan,
            'nm_tindakan' => $mapping->nm_tindakan,
            'kd_pj' => $mapping->kd_pj,
            'nm_pj' => $mapping->nm_pj,
            'parent_kd_tindakan' => $mapping->parent_kd_tindakan,
            'parent_nm_tindakan' => $mapping->parent_nm_tindakan,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    protected function formatSourceItem(array $item)
    {
        $parentLabel = $this->parentLabel($item['parent_kd_tindakan'], $item['parent_nm_tindakan']);
        $pjLabel = $item['nm_pj'] ?: ($item['kd_pj'] ?: '-');
        $displayText = trim($item['kd_tindakan'] . ' - ' . $item['nm_tindakan']);

        if ($parentLabel !== '-') {
            $displayText .= ' / ' . $parentLabel;
        }

        return [
            'source_key' => $item['source_key'],
            'sumber_tindakan' => $item['sumber_tindakan'],
            'sumber_label' => $item['sumber_label'],
            'kd_tindakan' => $item['kd_tindakan'],
            'nm_tindakan' => $item['nm_tindakan'],
            'kd_pj' => $item['kd_pj'],
            'nm_pj' => $item['nm_pj'],
            'pj_label' => $pjLabel,
            'parent_kd_tindakan' => $item['parent_kd_tindakan'],
            'parent_nm_tindakan' => $item['parent_nm_tindakan'],
            'parent_label' => $parentLabel,
            'display_text' => $displayText,
        ];
    }

    protected function formatMappingItem($item)
    {
        $sourceKey = $this->sourceKeyFromMapping($item);
        $parentLabel = $this->parentLabel($item->parent_kd_tindakan, $item->parent_nm_tindakan);
        $pjLabel = $item->nm_pj ?: ($item->kd_pj ?: '-');
        $source = $item->sumber_tindakan ?: 'LEGACY';
        $sourceLabel = $this->sourceLabel($source);

        return [
            'id' => $item->id,
            'jnsTindakan_id' => $item->jnsTindakan_id,
            'source_key' => $sourceKey,
            'sumber_tindakan' => $source,
            'sumber_label' => $sourceLabel,
            'kd_tindakan' => $item->kd_tindakan,
            'nm_tindakan' => $item->nm_tindakan,
            'kd_pj' => $item->kd_pj,
            'nm_pj' => $item->nm_pj,
            'pj_label' => $pjLabel,
            'parent_kd_tindakan' => $item->parent_kd_tindakan,
            'parent_nm_tindakan' => $item->parent_nm_tindakan,
            'parent_label' => $parentLabel,
            'display_text' => trim($item->kd_tindakan . ' - ' . $item->nm_tindakan),
        ];
    }

    protected function sourceKeyFromMapping($item)
    {
        return ($item->sumber_tindakan ?: 'LEGACY') . ':' . $item->kd_tindakan;
    }

    protected function parentLabel($parentKode, $parentNama)
    {
        if (!$parentKode && !$parentNama) {
            return '-';
        }

        if ($parentKode && $parentNama) {
            return $parentKode . ' - ' . $parentNama;
        }

        return $parentNama ?: $parentKode;
    }
}
