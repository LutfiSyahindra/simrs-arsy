<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generatePremiDriverConfigModel;
use App\Models\dbSimrs\generatePremiDriverModel;
use App\Models\dbSimrs\generatePremiDriverTujuanModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generatePremiDriverRepository
{
    public function getResults(?string $periode = null): Collection
    {
        return generatePremiDriverModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->orderByDesc('periode')
            ->orderBy('pegawai_name')
            ->get();
    }

    public function getSummary(?string $periode): array
    {
        $rows = $periode
            ? generatePremiDriverModel::query()
                ->where('periode', $periode)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_pegawai' => $rows->pluck('pegawai_id')->unique()->count(),
            'jumlah_tujuan' => $rows->sum('jumlah_tujuan'),
            'total_jumlah' => $rows->sum('total_jumlah'),
            'grand_total' => $rows->sum('grand_total'),
            'total_premi_pegawai' => $rows->sum('total_premi_pegawai'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
        ];
    }

    public function getConfig(): generatePremiDriverConfigModel
    {
        return $this->ensureDefaultConfig();
    }

    public function saveConfig(float $premiBersamaPercent): generatePremiDriverConfigModel
    {
        return DB::transaction(function () use ($premiBersamaPercent) {
            $config = generatePremiDriverConfigModel::query()
                ->lockForUpdate()
                ->first();

            if (! $config) {
                $config = generatePremiDriverConfigModel::query()->create([
                    'premi_bersama_percent' => 20,
                ]);
            }

            $config->update([
                'premi_bersama_percent' => $premiBersamaPercent,
            ]);

            return $config->fresh();
        });
    }

    public function getTujuanList(?bool $active = null): Collection
    {
        return generatePremiDriverTujuanModel::query()
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->orderBy('sort_order')
            ->orderBy('nama_tujuan')
            ->get();
    }

    public function findTujuan(int $id): ?generatePremiDriverTujuanModel
    {
        return generatePremiDriverTujuanModel::query()->find($id);
    }

    public function findTujuanForUpdate(int $id): ?generatePremiDriverTujuanModel
    {
        return generatePremiDriverTujuanModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findActiveTujuan(int $id): ?generatePremiDriverTujuanModel
    {
        return generatePremiDriverTujuanModel::query()
            ->where('is_active', true)
            ->find($id);
    }

    public function kodeTujuanExists(string $kode, ?int $ignoreId = null): bool
    {
        return generatePremiDriverTujuanModel::query()
            ->where('kode', $kode)
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists();
    }

    public function saveTujuan(array $payload, ?int $id = null): generatePremiDriverTujuanModel
    {
        return DB::transaction(function () use ($payload, $id) {
            if ($id) {
                $tujuan = $this->findTujuanForUpdate($id);

                if (! $tujuan) {
                    abort(404, 'Tujuan ambulance tidak ditemukan.');
                }

                $tujuan->update($payload);

                return $tujuan->fresh();
            }

            return generatePremiDriverTujuanModel::query()->create($payload);
        });
    }

    public function deleteTujuan(int $id): void
    {
        DB::transaction(function () use ($id) {
            $tujuan = $this->findTujuanForUpdate($id);

            if (! $tujuan) {
                abort(404, 'Tujuan ambulance tidak ditemukan.');
            }

            $tujuan->delete();
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

    public function findExistingForUpdate(string $periode, string $pegawaiId): ?generatePremiDriverModel
    {
        return generatePremiDriverModel::query()
            ->where('periode', $periode)
            ->where('pegawai_id', $pegawaiId)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(
        string $periode,
        object $pegawai,
        array $calculation
    ): generatePremiDriverModel {
        $result = generatePremiDriverModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'pegawai_id' => $pegawai->nik,
            ],
            [
                'pegawai_name' => $pegawai->nama,
                'pegawai_position' => $pegawai->jbtn,
                'jumlah_tujuan' => $calculation['jumlah_tujuan'],
                'total_jumlah' => $calculation['total_jumlah'],
                'grand_total' => $calculation['grand_total'],
                'premi_pegawai_percent' => $calculation['premi_pegawai_percent'],
                'total_premi_pegawai' => $calculation['total_premi_pegawai'],
                'premi_bersama_percent' => $calculation['premi_bersama_percent'],
                'total_premi_bersama' => $calculation['total_premi_bersama'],
                'config_snapshot' => $calculation['config_snapshot'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();

        $now = now();
        $details = collect($calculation['details'])->map(fn ($item) => [
            'generate_premi_driver_id' => $result->id,
            'tujuan_id' => $item['tujuan_id'],
            'kode_tujuan' => $item['kode_tujuan'],
            'nama_tujuan' => $item['nama_tujuan'],
            'harga' => $item['harga'],
            'jumlah' => $item['jumlah'],
            'subtotal' => $item['subtotal'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($details) {
            DB::table('generate_premi_driver_detail')->insert($details);
        }

        return $result->fresh(['details', 'lockedBy:id,name', 'generateBy:id,name']);
    }

    public function findForUpdate(int $id): ?generatePremiDriverModel
    {
        return generatePremiDriverModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generatePremiDriverModel
    {
        return generatePremiDriverModel::query()
            ->with([
                'details' => fn ($query) => $query->orderBy('nama_tujuan'),
                'lockedBy:id,name',
                'generateBy:id,name',
            ])
            ->find($id);
    }

    public function updateLock(
        generatePremiDriverModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generatePremiDriverModel {
        DB::table('generate_premi_driver')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generatePremiDriverModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generatePremiDriverModel $result): void
    {
        $result->delete();
    }

    private function ensureDefaultConfig(): generatePremiDriverConfigModel
    {
        return generatePremiDriverConfigModel::query()->firstOrCreate(
            [],
            ['premi_bersama_percent' => 20]
        );
    }
}
