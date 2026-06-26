<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateCasemixConfigModel;
use App\Models\dbSimrs\generateCasemixModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateCasemixRepository
{
    public function getResults(?string $periode = null): Collection
    {
        return generateCasemixModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->orderByDesc('periode')
            ->get();
    }

    public function getSummary(?string $periode): array
    {
        $rows = $periode
            ? generateCasemixModel::query()->where('periode', $periode)->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'biaya_rs' => $rows->sum('biaya_rs'),
            'tarif_bpjs' => $rows->sum('tarif_bpjs'),
            'verifikasi_hasil_bpjs' => $rows->sum('verifikasi_hasil_bpjs'),
            'total_reward' => $rows->sum('total_reward'),
            'team_pool' => $rows->sum('team_pool'),
            'non_team_pool' => $rows->sum('non_team_pool'),
            'leader_total' => $rows->sum('leader_total'),
            'kanit_total' => $rows->sum('kanit_total'),
            'inputer_total' => $rows->sum('inputer_total'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
            'score_percent' => round((float) $rows->avg('score_percent'), 2),
            'reward_percent' => round((float) $rows->avg('reward_percent'), 4),
        ];
    }

    public function getConfig(): generateCasemixConfigModel
    {
        $this->ensureDefaultConfig();

        return generateCasemixConfigModel::query()
            ->with('pegawai')
            ->where('config_key', 'default')
            ->firstOrFail();
    }

    public function saveConfig(array $payload, array $recipients): generateCasemixConfigModel
    {
        $this->ensureDefaultConfig();

        return DB::transaction(function () use ($payload, $recipients) {
            $config = generateCasemixConfigModel::query()
                ->where('config_key', 'default')
                ->lockForUpdate()
                ->firstOrFail();

            $config->update($payload);
            $config->pegawai()->delete();

            $now = now();
            $rows = [];
            foreach ($recipients as $role => $items) {
                foreach ($items as $item) {
                    $rows[] = [
                        'config_id' => $config->id,
                        'role' => $role,
                        'pegawai_id' => $item['pegawai_id'],
                        'pegawai_name' => $item['pegawai_name'],
                        'pegawai_position' => $item['pegawai_position'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows) {
                DB::table('generate_casemix_config_pegawai')->insert($rows);
            }

            return $config->fresh('pegawai');
        });
    }

    public function searchPegawai(?string $keyword = null): Collection
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('stts_aktif', 'AKTIF')
            ->where('nik', '!=', '-')
            ->where('nama', '!=', '-')
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
            ->where('nik', '!=', '-')
            ->where('nama', '!=', '-')
            ->first();
    }

    public function findExistingForUpdate(string $periode): ?generateCasemixModel
    {
        return generateCasemixModel::query()
            ->where('periode', $periode)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, array $calculation): generateCasemixModel
    {
        $result = generateCasemixModel::query()->updateOrCreate(
            ['periode' => $periode],
            [
                'biaya_rs' => $calculation['input']['biaya_rs'],
                'tarif_bpjs' => $calculation['input']['tarif_bpjs'],
                'verifikasi_hasil_bpjs' => $calculation['input']['verifikasi_hasil_bpjs'],
                'kerugian_awal_percent' => $calculation['kerugian_awal_percent'],
                'total_score' => $calculation['score']['total_score'],
                'max_score' => $calculation['score']['max_score'],
                'score_percent' => $calculation['score']['score_percent'],
                'reward_percent' => $calculation['reward_percent'],
                'total_reward' => $calculation['pools']['total_reward'],
                'team_pool_percent' => $calculation['pools']['team_pool_percent'],
                'team_pool' => $calculation['pools']['team_pool'],
                'non_team_pool' => $calculation['pools']['non_team_pool'],
                'leader_total' => $calculation['pools']['leader_total'],
                'kanit_total' => $calculation['pools']['kanit_total'],
                'inputer_total' => $calculation['pools']['inputer_total'],
                'total_dibagikan' => $calculation['total_dibagikan'],
                'config_snapshot' => $calculation['config_snapshot'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();

        $now = now();
        collect($calculation['details'])
            ->map(fn (array $detail) => [
                ...$detail,
                'generate_casemix_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(200)
            ->each(fn (Collection $chunk) => DB::table('generate_casemix_detail')->insert($chunk->all()));

        return $result->fresh([
            'details',
            'lockedBy:id,name',
            'generateBy:id,name',
        ]);
    }

    public function findForUpdate(int $id): ?generateCasemixModel
    {
        return generateCasemixModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateCasemixModel
    {
        return generateCasemixModel::query()
            ->with([
                'details' => fn ($query) => $query
                    ->orderByRaw("FIELD(row_type, 'questionnaire', 'recipient')")
                    ->orderByRaw("FIELD(role, 'leader', 'kanit', 'inputer')")
                    ->orderBy('id'),
                'lockedBy:id,name',
                'generateBy:id,name',
            ])
            ->find($id);
    }

    public function updateLock(
        generateCasemixModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateCasemixModel {
        DB::table('generate_casemix')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateCasemixModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateCasemixModel $result): void
    {
        $result->delete();
    }

    private function ensureDefaultConfig(): void
    {
        generateCasemixConfigModel::query()->firstOrCreate(
            ['config_key' => 'default'],
            [
                'excellent_min_percent' => 90,
                'excellent_reward_percent' => 1.25,
                'good_min_percent' => 80,
                'good_reward_percent' => 1,
                'low_reward_percent' => 0.75,
                'team_pool_percent' => 70,
                'leader_percent' => 68,
                'kanit_percent' => 12,
                'inputer_percent' => 20,
                'question_config' => null,
            ]
        );
    }
}
