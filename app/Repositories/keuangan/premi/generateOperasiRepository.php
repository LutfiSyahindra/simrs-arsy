<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateOperasiConfigModel;
use App\Models\dbSimrs\generateOperasiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateOperasiRepository
{
    public function getResults(?string $periode = null, ?string $jenisOperasi = null): Collection
    {
        return generateOperasiModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisOperasi, fn ($query) => $query->where('jenis_operasi', $jenisOperasi))
            ->orderByDesc('periode')
            ->orderBy('jenis_operasi')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisOperasi): array
    {
        $rows = $periode
            ? generateOperasiModel::query()
                ->where('periode', $periode)
                ->where('jenis_operasi', $jenisOperasi)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'total_operasi' => $rows->sum('total_operasi'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_instrumen_kelompok_20')
                + $rows->sum('total_instrumen_kelompok_80')
                + $rows->sum('total_dokter_anastesi')
                + $rows->sum('total_perawat_anastesi'),
        ];
    }

    public function getConfig(string $jenisOperasi): generateOperasiConfigModel
    {
        $this->ensureDefaultConfigs();

        return generateOperasiConfigModel::query()
            ->with('pegawai')
            ->where('jenis_operasi', $jenisOperasi)
            ->firstOrFail();
    }

    public function saveConfig(string $jenisOperasi, array $percentages, array $recipients): generateOperasiConfigModel
    {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisOperasi, $percentages, $recipients) {
            $config = generateOperasiConfigModel::query()
                ->where('jenis_operasi', $jenisOperasi)
                ->lockForUpdate()
                ->firstOrFail();

            $config->update($percentages);
            $config->pegawai()->delete();

            $rows = [];
            $now = now();
            foreach ($recipients as $role => $items) {
                foreach ($items as $item) {
                    $rows[] = [
                        'config_id' => $config->id,
                        'role' => $role,
                        'pegawai_source' => $item['pegawai_source'],
                        'pegawai_id' => $item['pegawai_id'],
                        'pegawai_name' => $item['pegawai_name'],
                        'pegawai_position' => $item['pegawai_position'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows) {
                DB::table('generate_operasi_config_pegawai')->insert($rows);
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

    public function searchDokter(?string $keyword = null): Collection
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

    public function findPegawai(string $nik): ?object
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('nik', $nik)
            ->where('stts_aktif', 'AKTIF')
            ->first();
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

    public function findExistingForUpdate(string $periode, string $jenisOperasi): ?generateOperasiModel
    {
        return generateOperasiModel::query()
            ->where('periode', $periode)
            ->where('jenis_operasi', $jenisOperasi)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisOperasi, array $calculation): generateOperasiModel
    {
        $result = generateOperasiModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_operasi' => $jenisOperasi,
            ],
            [
                'total_operasi' => $calculation['total_operasi'],
                'total_instrumen' => $calculation['pools']['instrumen'],
                'total_premi_bersama' => $calculation['pools']['premi_bersama'],
                'total_instrumen_petugas' => $calculation['pools']['instrumen_petugas'],
                'total_instrumen_kelompok_20' => $calculation['pools']['instrumen_kelompok_20'],
                'total_instrumen_kelompok_80' => $calculation['pools']['instrumen_kelompok_80'],
                'total_dokter_anastesi' => $calculation['pools']['dokter_anastesi'],
                'total_perawat_anastesi' => $calculation['pools']['perawat_anastesi'],
                'config_snapshot' => $calculation['config'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();

        $now = now();
        $details = collect($calculation['recipients'])->map(fn ($item) => [
            'generate_operasi_id' => $result->id,
            'role' => $item['role'],
            'role_label' => $item['role_label'],
            'pegawai_source' => $item['pegawai_source'],
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
            DB::table('generate_operasi_details')->insert($details);
        }

        return $result->fresh(['details', 'lockedBy:id,name', 'generateBy:id,name']);
    }

    public function findForUpdate(int $id): ?generateOperasiModel
    {
        return generateOperasiModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateOperasiModel
    {
        return generateOperasiModel::query()
            ->with([
                'details' => fn ($query) => $query
                    ->orderByRaw("FIELD(role, 'instrumen_20', 'instrumen_80', 'dokter_anastesi', 'perawat_anastesi')")
                    ->orderBy('pegawai_name'),
                'lockedBy:id,name',
                'generateBy:id,name',
            ])
            ->find($id);
    }

    public function updateLock(generateOperasiModel $result, bool $isLocked, ?int $userId = null): generateOperasiModel
    {
        DB::table('generate_operasi')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateOperasiModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateOperasiModel $result): void
    {
        $result->delete();
    }

    private function ensureDefaultConfigs(): void
    {
        foreach (['umum', 'bpjs'] as $jenis) {
            generateOperasiConfigModel::query()->firstOrCreate(
                ['jenis_operasi' => $jenis],
                [
                    'instrumen_percent' => 10,
                    'instrumen_premi_bersama_percent' => 20,
                    'instrumen_petugas_percent' => 80,
                    'instrumen_petugas_kelompok_20_percent' => 20,
                    'instrumen_petugas_kelompok_80_percent' => 80,
                    'dokter_anastesi_percent' => 40,
                    'perawat_anastesi_percent' => 10,
                    'perawat_anastesi_petugas_percent' => 80,
                    'perawat_anastesi_premi_bersama_percent' => 20,
                ]
            );
        }
    }
}
