<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateVkRepository;
use App\Services\mappingData\tindakanMappingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateVkService
{
    public function __construct(
        protected generateVkRepository $repository,
        protected tindakanMappingService $tindakanMappingService
    ) {}

    public function getResults(?string $periode = null, ?string $jenisVk = null)
    {
        return $this->repository
            ->getResults($periode, $jenisVk)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisVk): array
    {
        return [
            'periode' => $periode,
            'jenis_vk' => $jenisVk,
            'jenis_vk_label' => $this->typeLabel($jenisVk),
            ...$this->repository->getSummary($periode, $jenisVk),
        ];
    }

    public function getTindakanOptions(?string $keyword = null)
    {
        return $this->tindakanMappingService
            ->searchTindakan((string) $keyword)
            ->values();
    }

    public function getPlotingOptions()
    {
        return $this->repository
            ->getPlotingPremi()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'ploting' => $item->ploting,
                'text' => trim($item->kode.' - '.$item->ploting),
            ])
            ->values();
    }

    public function copyPreview(string $periode, string $jenisVk): array
    {
        $targetPeriode = Carbon::createFromFormat('Y-m-d', $periode.'-01')
            ->addMonthNoOverflow()
            ->format('Y-m');
        $items = $this->repository
            ->getResults($periode, $jenisVk)
            ->map(fn ($row) => $this->resultPayload($row))
            ->values();

        return [
            'source_periode' => $periode,
            'target_periode' => $targetPeriode,
            'jenis_vk' => $jenisVk,
            'jenis_vk_label' => $this->typeLabel($jenisVk),
            'count' => $items->count(),
            'jumlah_tindakan' => $items->sum('jumlah_tindakan'),
            'total_vk' => $items->sum('total_vk'),
            'items' => $items,
        ];
    }

    public function generate(
        string $periode,
        string $jenisVk,
        string $sourceKey,
        int $plotingId,
        int $jumlahTindakan,
        int $nominal
    ): array {
        return DB::transaction(
            fn () => $this->generateRow(
                $periode,
                $jenisVk,
                $sourceKey,
                $plotingId,
                $jumlahTindakan,
                $nominal
            )
        );
    }

    public function generateMany(
        string $periode,
        string $jenisVk,
        array $entries
    ): array {
        return DB::transaction(function () use ($periode, $jenisVk, $entries) {
            $results = [];
            $seen = [];

            foreach ($entries as $index => $entry) {
                $sourceKey = (string) $entry['source_key'];
                $plotingId = (int) $entry['plotingPremi_id'];
                $key = $sourceKey.'|'.$plotingId;

                if (isset($seen[$key])) {
                    throw ValidationException::withMessages([
                        "entries.{$index}.source_key" => 'Tindakan dan ploting tidak boleh duplikat dalam satu generate.',
                    ]);
                }

                $seen[$key] = true;
                $results[] = $this->generateRow(
                    $periode,
                    $jenisVk,
                    $sourceKey,
                    $plotingId,
                    (int) $entry['jumlah_tindakan'],
                    (int) $entry['nominal_hitung'],
                    "entries.{$index}."
                );
            }

            return [
                'periode' => $periode,
                'jenis_vk' => $jenisVk,
                'jenis_vk_label' => $this->typeLabel($jenisVk),
                'count' => count($results),
                'jumlah_tindakan' => collect($results)->sum('jumlah_tindakan'),
                'total_vk' => collect($results)->sum('total_vk'),
                'items' => $results,
            ];
        });
    }

    private function generateRow(
        string $periode,
        string $jenisVk,
        string $sourceKey,
        int $plotingId,
        int $jumlahTindakan,
        int $nominal,
        string $errorPrefix = ''
    ): array {
        $tindakan = $this->tindakanMappingService
            ->findTindakanByKeys([$sourceKey])
            ->get($sourceKey);

        if (! $tindakan) {
            throw ValidationException::withMessages([
                $errorPrefix.'source_key' => 'Tindakan tidak ditemukan di data Khanza.',
            ]);
        }

        $ploting = $this->repository->findPloting($plotingId);

        if (! $ploting) {
            throw ValidationException::withMessages([
                $errorPrefix.'plotingPremi_id' => 'Ploting premi tidak ditemukan.',
            ]);
        }

        $existing = $this->repository->findExistingForUpdate(
            $periode,
            $jenisVk,
            $tindakan['sumber_tindakan'],
            $tindakan['kd_tindakan'],
            $plotingId
        );

        if ($existing?->is_locked) {
            throw ValidationException::withMessages([
                $errorPrefix.'source_key' => 'Data VK tindakan dan ploting ini sudah dikunci.',
            ]);
        }

        return $this->resultPayload(
            $this->repository->saveResult(
                $periode,
                $jenisVk,
                $tindakan,
                $ploting,
                $jumlahTindakan,
                $nominal
            )->fresh(['lockedBy:id,name', 'generateBy:id,name'])
        );
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate VK tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data sudah dalam keadaan terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, true, $user->id)
            );
        });
    }

    public function unlock(int $id, User $user): array
    {
        if (! $user->hasRole('Admin')) {
            throw new AuthorizationException(
                'Hanya user dengan role Admin yang dapat membuka kunci data.'
            );
        }

        return DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate VK tidak ditemukan.');
            }

            if (! $result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data tidak sedang terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, false)
            );
        });
    }

    private function resultPayload($row): array
    {
        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'jenis_vk' => $row->jenis_vk,
            'jenis_vk_label' => $this->typeLabel($row->jenis_vk),
            'source_key' => $row->source_key,
            'sumber_tindakan' => $row->sumber_tindakan,
            'sumber_label' => $this->tindakanMappingService->sourceLabel($row->sumber_tindakan),
            'kd_tindakan' => $row->kd_tindakan,
            'nm_tindakan' => $row->nm_tindakan,
            'kd_pj' => $row->kd_pj,
            'nm_pj' => $row->nm_pj,
            'pj_label' => $row->nm_pj ?: ($row->kd_pj ?: '-'),
            'parent_kd_tindakan' => $row->parent_kd_tindakan,
            'parent_nm_tindakan' => $row->parent_nm_tindakan,
            'parent_label' => $this->parentLabel($row->parent_kd_tindakan, $row->parent_nm_tindakan),
            'display_text' => trim($row->kd_tindakan.' - '.$row->nm_tindakan),
            'plotingPremi_id' => $row->plotingPremi_id,
            'kode_ploting' => $row->kode_ploting,
            'nama_ploting' => $row->nama_ploting,
            'ploting_label' => trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-')),
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'nominal_hitung' => $row->nominal_hitung,
            'total_vk' => $row->total_vk,
            'is_locked' => $row->is_locked,
            'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row->lockedBy?->name,
            'generate_by_name' => $row->generateBy?->name,
            'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function lockPayload($result): array
    {
        return [
            'id' => $result->id,
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
        ];
    }

    private function typeLabel(string $jenisVk): string
    {
        return $jenisVk === 'bpjs' ? 'BPJS' : 'Umum';
    }

    private function parentLabel($parentKode, $parentNama): string
    {
        if (! $parentKode && ! $parentNama) {
            return '-';
        }

        if ($parentKode && $parentNama) {
            return $parentKode.' - '.$parentNama;
        }

        return $parentNama ?: $parentKode;
    }
}
