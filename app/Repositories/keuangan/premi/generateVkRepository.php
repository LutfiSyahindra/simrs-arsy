<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateVkConfigModel;
use App\Models\dbSimrs\generateVkModel;
use App\Models\dbSimrs\plotingPremiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateVkRepository
{
    public function getResults(?string $periode = null, ?string $jenisVk = null): Collection
    {
        return generateVkModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisVk, fn ($query) => $query->where('jenis_vk', $jenisVk))
            ->orderByDesc('periode')
            ->orderBy('jenis_vk')
            ->orderBy('nm_tindakan')
            ->orderBy('nama_ploting')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisVk): array
    {
        $rows = $periode
            ? generateVkModel::query()
                ->where('periode', $periode)
                ->where('jenis_vk', $jenisVk)
                ->get()
            : collect();

        return [
            'jumlah_tindakan' => $rows->pluck('source_key')->unique()->count(),
            'total_jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'total_vk' => $rows->sum('total_vk'),
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'total_vk_awal' => $rows->sum('total_vk_awal') ?: $rows->sum('total_vk'),
            'bpjs_pool' => $rows->sum('bpjs_pool'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
            'ploting_summaries' => $this->plotingSummaries($rows),
        ];
    }

    private function plotingSummaries(Collection $rows): array
    {
        return $rows
            ->groupBy(fn ($row) => $row->plotingPremi_id ?: 'tanpa-ploting')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'plotingPremi_id' => $first?->plotingPremi_id,
                    'kode_ploting' => $first?->kode_ploting,
                    'nama_ploting' => $first?->nama_ploting,
                    'ploting_label' => trim(($first?->kode_ploting ? $first->kode_ploting.' - ' : '').($first?->nama_ploting ?? 'Tanpa Ploting')),
                    'generated_count' => $items->count(),
                    'jumlah_tindakan' => $items->sum('jumlah_tindakan'),
                    'total_vk' => $items->sum('total_vk'),
                    'total_dibagikan' => $items->sum('total_dibagikan'),
                    'locked_count' => $items->where('is_locked', true)->count(),
                ];
            })
            ->sortBy(fn ($item) => strtolower($item['ploting_label']))
            ->values()
            ->toArray();
    }

    public function getPlotingPremi(): Collection
    {
        return plotingPremiModel::query()
            ->select('id', 'kode', 'ploting')
            ->orderBy('ploting')
            ->get();
    }

    public function getConfig(string $jenisVk): generateVkConfigModel
    {
        $this->ensureDefaultConfigs();

        return generateVkConfigModel::query()
            ->with('pegawai')
            ->where('jenis_vk', $jenisVk)
            ->firstOrFail();
    }

    public function saveConfig(string $jenisVk, array $payload, array $recipients): generateVkConfigModel
    {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisVk, $payload, $recipients) {
            $config = generateVkConfigModel::query()
                ->where('jenis_vk', $jenisVk)
                ->lockForUpdate()
                ->firstOrFail();

            $config->update($payload);
            $config->pegawai()->delete();

            $now = now();
            $rows = collect($recipients)->map(fn ($item) => [
                'config_id' => $config->id,
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows) {
                DB::table('generate_vk_config_pegawai')->insert($rows);
            }

            return $config->fresh('pegawai');
        });
    }

    public function searchPegawai(?string $keyword = null): Collection
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('stts_aktif', 'AKTIF')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('nik', 'like', "%{$keyword}%")
                        ->orWhere('nama', 'like', "%{$keyword}%")
                        ->orWhere('jbtn', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('nama')
            ->limit(50)
            ->get();
    }

    public function findPegawai(string $nik): ?object
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('nik', $nik)
            ->where('stts_aktif', 'AKTIF')
            ->first();
    }

    public function findPloting(int $id): ?plotingPremiModel
    {
        return plotingPremiModel::query()->find($id);
    }

    public function findExistingForUpdate(
        string $periode,
        string $jenisVk,
        string $sumberTindakan,
        string $kdTindakan,
        int $plotingId
    ): ?generateVkModel {
        return generateVkModel::query()
            ->where('periode', $periode)
            ->where('jenis_vk', $jenisVk)
            ->where('sumber_tindakan', $sumberTindakan)
            ->where('kd_tindakan', $kdTindakan)
            ->where('plotingPremi_id', $plotingId)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(
        string $periode,
        string $jenisVk,
        array $tindakan,
        plotingPremiModel $ploting,
        int $jumlahTindakan,
        int $nominal,
        array $calculation
    ): generateVkModel {
        $result = generateVkModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_vk' => $jenisVk,
                'sumber_tindakan' => $tindakan['sumber_tindakan'],
                'kd_tindakan' => $tindakan['kd_tindakan'],
                'plotingPremi_id' => $ploting->id,
            ],
            [
                'source_key' => $tindakan['source_key'],
                'nm_tindakan' => $tindakan['nm_tindakan'],
                'kd_pj' => $tindakan['kd_pj'],
                'nm_pj' => $tindakan['nm_pj'],
                'parent_kd_tindakan' => $tindakan['parent_kd_tindakan'],
                'parent_nm_tindakan' => $tindakan['parent_nm_tindakan'],
                'kode_ploting' => $ploting->kode,
                'nama_ploting' => $ploting->ploting,
                'jumlah_tindakan' => $jumlahTindakan,
                'nominal_hitung' => $nominal,
                'total_vk' => $calculation['total_vk'],
                'total_vk_awal' => $calculation['total_vk_awal'],
                'bpjs_pool' => $calculation['bpjs_pool'],
                'total_dibagikan' => $calculation['total_dibagikan'],
                'config_snapshot' => $calculation['config_snapshot'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();

        $now = now();
        $details = collect($calculation['recipients'])->map(fn ($item) => [
            'generate_vk_id' => $result->id,
            'role' => $item['role'],
            'role_label' => $item['role_label'],
            'pegawai_id' => $item['pegawai_id'],
            'pegawai_name' => $item['pegawai_name'],
            'pegawai_position' => $item['pegawai_position'],
            'allocation_percent' => $item['allocation_percent'],
            'pool_total' => $item['pool_total'],
            'total_received' => $item['total_received'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($details) {
            DB::table('generate_vk_detail')->insert($details);
        }

        return $result;
    }

    public function findForUpdate(int $id): ?generateVkModel
    {
        return generateVkModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function getUnlockedForPeriodAndType(string $periode, string $jenisVk): Collection
    {
        return generateVkModel::query()
            ->where('periode', $periode)
            ->where('jenis_vk', $jenisVk)
            ->where('is_locked', false)
            ->lockForUpdate()
            ->get();
    }

    public function updateLock(
        generateVkModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateVkModel {
        DB::table('generate_vk')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateVkModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function updateManyLock(Collection $results, int $userId): int
    {
        $ids = $results->pluck('id')->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table('generate_vk')
            ->whereIn('id', $ids)
            ->update([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $userId,
            ]);
    }

    public function deleteResult(generateVkModel $result): void
    {
        $result->delete();
    }

    private function ensureDefaultConfigs(): void
    {
        foreach (['umum', 'bpjs'] as $jenis) {
            generateVkConfigModel::query()->firstOrCreate(
                ['jenis_vk' => $jenis],
                [
                    'bpjs_percent' => $jenis === 'bpjs' ? 4 : 100,
                    'bpjs_pembagi' => 4,
                    'distribution_mode' => 'rata',
                ]
            );
        }
    }
}
