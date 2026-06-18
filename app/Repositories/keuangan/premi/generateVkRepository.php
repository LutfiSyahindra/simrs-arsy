<?php

namespace App\Repositories\keuangan\premi;

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
        ];
    }

    public function getPlotingPremi(): Collection
    {
        return plotingPremiModel::query()
            ->select('id', 'kode', 'ploting')
            ->orderBy('ploting')
            ->get();
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
        int $nominal
    ): generateVkModel {
        return generateVkModel::query()->updateOrCreate(
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
                'total_vk' => $jumlahTindakan * $nominal,
                'generate_by' => Auth::id(),
            ]
        );
    }

    public function findForUpdate(int $id): ?generateVkModel
    {
        return generateVkModel::query()
            ->lockForUpdate()
            ->find($id);
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
}
