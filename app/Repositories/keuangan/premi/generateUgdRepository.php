<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generateUgdModel;
use App\Models\dbSimrs\plotingPremiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateUgdRepository
{
    public function getResults(?string $periode = null, ?string $jenisUgd = null): Collection
    {
        return generateUgdModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisUgd, fn ($query) => $query->where('jenis_ugd', $jenisUgd))
            ->orderByDesc('periode')
            ->orderBy('jenis_ugd')
            ->orderBy('nm_dokter')
            ->orderBy('nama_ploting')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisUgd): array
    {
        $rows = $periode
            ? generateUgdModel::query()
                ->where('periode', $periode)
                ->where('jenis_ugd', $jenisUgd)
                ->get()
            : collect();

        return [
            'jumlah_dokter' => $rows->pluck('kd_dokter')->unique()->count(),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'total_ugd' => $rows->sum('total_ugd'),
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
        ];
    }

    public function getDokterOptions(?string $keyword = null): Collection
    {
        return DB::connection('mysql_khanza')
            ->table('dokter')
            ->select('kd_dokter', 'nm_dokter')
            ->whereNotIn('kd_dokter', ['-', ''])
            ->whereNotIn('nm_dokter', ['-', ''])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('kd_dokter', 'like', "%{$keyword}%")
                        ->orWhere('nm_dokter', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('nm_dokter')
            ->limit(50)
            ->get();
    }

    public function findDokter(string $kdDokter): ?object
    {
        return DB::connection('mysql_khanza')
            ->table('dokter')
            ->select('kd_dokter', 'nm_dokter')
            ->where('kd_dokter', $kdDokter)
            ->whereNotIn('kd_dokter', ['-', ''])
            ->whereNotIn('nm_dokter', ['-', ''])
            ->first();
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
        string $jenisUgd,
        string $kdDokter,
        int $plotingId
    ): ?generateUgdModel {
        return generateUgdModel::query()
            ->where('periode', $periode)
            ->where('jenis_ugd', $jenisUgd)
            ->where('kd_dokter', $kdDokter)
            ->where('plotingPremi_id', $plotingId)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(
        string $periode,
        string $jenisUgd,
        object $dokter,
        plotingPremiModel $ploting,
        int $jumlahPasien,
        int $nominal
    ): generateUgdModel {
        return generateUgdModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_ugd' => $jenisUgd,
                'kd_dokter' => $dokter->kd_dokter,
                'plotingPremi_id' => $ploting->id,
            ],
            [
                'nm_dokter' => $dokter->nm_dokter,
                'kode_ploting' => $ploting->kode,
                'nama_ploting' => $ploting->ploting,
                'jumlah_pasien' => $jumlahPasien,
                'nominal_hitung' => $nominal,
                'total_ugd' => $jumlahPasien * $nominal,
                'generate_by' => Auth::id(),
            ]
        );
    }

    public function findForUpdate(int $id): ?generateUgdModel
    {
        return generateUgdModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function updateLock(
        generateUgdModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateUgdModel {
        DB::table('generate_ugd')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateUgdModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }
}
