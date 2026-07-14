<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateBhpRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateBhpService
{
    public function __construct(
        protected generateBhpRepository $generateBhpRepository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisBhp = null)
    {
        return $this->generateBhpRepository
            ->getResults($periode, $jenisBhp)
            ->map(fn ($row) => [
                'id' => $row->id,
                'periode' => $row->periode,
                'periode_sumber' => PremiSourcePeriod::resolve(
                    $row->periode,
                    $row->jenis_bhp
                ),
                'jenis_bhp' => $row->jenis_bhp,
                'jenis_bhp_label' => $this->typeLabel($row->jenis_bhp),
                'plotingPremi_id' => $row->plotingPremi_id,
                'kode_ploting' => $row->kode_ploting,
                'nama_ploting' => $row->nama_ploting,
                'ploting_label' => trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-')),
                'jumlah_bhp' => $row->jumlah_bhp,
                'nominal_hitung' => $row->nominal_hitung,
                'total_bhp' => $row->total_bhp,
                'jumlah_detail' => $row->details_count,
                'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
                'is_locked' => $row->is_locked,
                'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
                'locked_by_name' => $row->lockedBy?->name,
                'generate_by_name' => $row->generateBy?->name,
            ]);
    }

    public function getSummary(?string $periode, string $jenisBhp): array
    {
        $rows = $periode
            ? $this->generateBhpRepository->findByPeriodAndType($periode, $jenisBhp)
            : collect();
        $firstRow = $rows->first();
        $lockedRows = $rows->where('is_locked', true);
        $lastLockedRow = $lockedRows->sortByDesc('locked_at')->first();
        $plotings = $this->generateBhpRepository->getPlotingPremi();
        $plotingCount = $plotings->count();
        $nominals = $rows
            ->pluck('nominal_hitung')
            ->filter(fn ($value) => (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        return [
            'periode' => $periode,
            'periode_sumber' => $periode
                ? PremiSourcePeriod::resolve($periode, $jenisBhp)
                : null,
            'jenis_bhp' => $jenisBhp,
            'jenis_bhp_label' => $this->typeLabel($jenisBhp),
            'jumlah_bhp' => $firstRow?->jumlah_bhp ?? 0,
            'nominal_hitung' => $firstRow?->nominal_hitung ?? 0,
            'nominal_is_mixed' => $nominals->count() > 1,
            'total_bhp' => $rows->sum('total_bhp'),
            'ploting_count' => $plotingCount,
            'generated_ploting_count' => $rows->count(),
            'locked_ploting_count' => $lockedRows->count(),
            'is_locked' => $lockedRows->isNotEmpty(),
            'locked_at' => optional($lastLockedRow?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $lastLockedRow?->lockedBy?->name,
            'ploting_nominals' => $this->plotingNominalPayload($plotings, $rows),
        ];
    }

    public function getConfig(string $jenisBhp): array
    {
        return $this->configPayload($jenisBhp, $this->generateBhpRepository->getConfigs($jenisBhp));
    }

    public function updateConfig(string $jenisBhp, array $data): array
    {
        DB::transaction(function () use ($jenisBhp, $data) {
            foreach ($data['nominal_defaults'] ?? [] as $row) {
                $this->generateBhpRepository->saveConfig(
                    $jenisBhp,
                    (int) $row['plotingPremi_id'],
                    [
                        'default_nominal' => max(0, (int) ($row['default_nominal'] ?? 0)),
                    ]
                );
            }
        });

        return $this->getConfig($jenisBhp);
    }

    public function generate(string $periode, string $jenisBhp, array $nominalByPloting): array
    {
        $typeLabel = $this->typeLabel($jenisBhp);
        $sourcePeriod = PremiSourcePeriod::resolve($periode, $jenisBhp);

        return DB::transaction(function () use (
            $periode,
            $jenisBhp,
            $nominalByPloting,
            $typeLabel,
            $sourcePeriod
        ) {
            $plotings = $this->generateBhpRepository->getPlotingPremi();

            if ($plotings->isEmpty()) {
                throw ValidationException::withMessages([
                    'ploting' => 'Master Ploting Premi belum tersedia. Tambahkan data ploting terlebih dahulu.',
                ]);
            }

            $nominals = $this->normalizeNominals($plotings, $nominalByPloting);
            $existingRows = $this->generateBhpRepository
                ->findByPeriodAndTypeForUpdate($periode, $jenisBhp);
            $lockedRows = $existingRows->where('is_locked', true);

            if ($lockedRows->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => "BHP {$typeLabel} periode {$periode} memiliki {$lockedRows->count()} ploting yang sudah dikunci dan tidak dapat digenerate ulang.",
                ]);
            }

            $selectedPlotingIds = array_keys($nominals);
            $plotings = $plotings
                ->whereIn('id', $selectedPlotingIds)
                ->values();
            $usedSkippedRows = $existingRows
                ->whereNotIn('plotingPremi_id', $selectedPlotingIds)
                ->filter(fn ($row) => $this->generateBhpRepository
                    ->isUsedInPremiPelayananNonMedis($row->id));

            if ($usedSkippedRows->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'nominal_hitung' => "Ada {$usedSkippedRows->count()} data BHP bernominal 0 yang sudah dipakai pada premi pelayanan non-medis.",
                ]);
            }

            $details = $this->generateBhpRepository->getEligibleByType($periode, $jenisBhp);

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => "Tidak ada data rawat inap {$typeLabel} yang memenuhi kriteria pada periode sumber {$sourcePeriod}.",
                ]);
            }

            $this->generateBhpRepository->deleteExceptPlotingIds(
                $periode,
                $jenisBhp,
                $selectedPlotingIds
            );

            $results = $plotings->map(function ($ploting) use (
                $periode,
                $jenisBhp,
                $details,
                $nominals
            ) {
                $nominal = $nominals[$ploting->id];
                $result = $this->generateBhpRepository->saveResult(
                    $periode,
                    $jenisBhp,
                    $ploting,
                    $details->count(),
                    $nominal
                );

                $this->generateBhpRepository->replaceDetails($result, $details);

                return $result;
            });
            $firstResult = $results->first();

            return [
                'id' => $firstResult->id,
                'periode' => $firstResult->periode,
                'periode_sumber' => PremiSourcePeriod::resolve(
                    $firstResult->periode,
                    $firstResult->jenis_bhp
                ),
                'jenis_bhp' => $firstResult->jenis_bhp,
                'jenis_bhp_label' => $this->typeLabel($firstResult->jenis_bhp),
                'jumlah_bhp' => $details->count(),
                'nominal_hitung' => $firstResult->nominal_hitung,
                'nominal_is_mixed' => collect($nominals)->unique()->count() > 1,
                'total_bhp' => $results->sum('total_bhp'),
                'ploting_count' => $plotings->count(),
                'generated_count' => $results->count(),
            ];
        });
    }

    public function getDetail(int $id): array
    {
        $result = $this->generateBhpRepository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate BHP tidak ditemukan.');
        }

        $penjaminNames = $this->generateBhpRepository->getPenjaminNames(
            $result->details->pluck('kd_pj')
        );

        return [
            'id' => $result->id,
            'periode' => $result->periode,
            'periode_sumber' => PremiSourcePeriod::resolve(
                $result->periode,
                $result->jenis_bhp
            ),
            'jenis_bhp' => $result->jenis_bhp,
            'jenis_bhp_label' => $this->typeLabel($result->jenis_bhp),
            'plotingPremi_id' => $result->plotingPremi_id,
            'kode_ploting' => $result->kode_ploting,
            'nama_ploting' => $result->nama_ploting,
            'ploting_label' => trim(($result->kode_ploting ? $result->kode_ploting.' - ' : '').($result->nama_ploting ?? '-')),
            'jumlah_bhp' => $result->jumlah_bhp,
            'nominal_hitung' => $result->nominal_hitung,
            'total_bhp' => $result->total_bhp,
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'details' => $result->details->map(fn ($detail) => [
                'no_rawat' => $detail->no_rawat,
                'tgl_registrasi' => $detail->tgl_registrasi?->format('d-m-Y'),
                'kd_pj' => $detail->kd_pj,
                'nama_penjamin' => $detail->nama_penjamin
                    ?: ($penjaminNames[$detail->kd_pj] ?? $detail->kd_pj),
            ])->values(),
        ];
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->generateBhpRepository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate BHP tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data BHP sudah dalam keadaan terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->generateBhpRepository->updateLock($result, true, $user->id)
            );
        });
    }

    public function lockAll(string $periode, string $jenisBhp, User $user): array
    {
        return DB::transaction(function () use ($periode, $jenisBhp, $user) {
            $results = $this->generateBhpRepository
                ->getUnlockedForPeriodAndType($periode, $jenisBhp);

            if ($results->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Tidak ada data BHP terbuka yang bisa dikunci.',
                ]);
            }

            $lockedCount = $this->generateBhpRepository->updateManyLock(
                $results,
                $user->id
            );

            return [
                'periode' => $periode,
                'jenis_bhp' => $jenisBhp,
                'jenis_bhp_label' => $this->typeLabel($jenisBhp),
                'locked_count' => $lockedCount,
            ];
        });
    }

    public function unlock(int $id, User $user): array
    {
        if (! $user->hasRole('Admin')) {
            throw new AuthorizationException(
                'Hanya user dengan role Admin yang dapat membuka kunci BHP.'
            );
        }

        return DB::transaction(function () use ($id) {
            $result = $this->generateBhpRepository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate BHP tidak ditemukan.');
            }

            if (! $result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data BHP tidak sedang terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->generateBhpRepository->updateLock($result, false)
            );
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $result = $this->generateBhpRepository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate BHP tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data BHP yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            if ($this->generateBhpRepository->isUsedInPremiPelayananNonMedis($result->id)) {
                throw ValidationException::withMessages([
                    'status' => 'Data BHP sudah dipakai pada premi pelayanan non-medis dan tidak dapat dihapus.',
                ]);
            }

            $this->generateBhpRepository->deleteResult($result);
        });
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

    private function normalizeNominals($plotings, array $nominalByPloting): array
    {
        $errors = [];
        $nominals = [];

        foreach ($plotings as $ploting) {
            $rawNominal = $nominalByPloting[$ploting->id]
                ?? $nominalByPloting[(string) $ploting->id]
                ?? null;
            $nominal = (int) preg_replace('/\D/', '', (string) $rawNominal);

            if ($nominal < 1) {
                continue;
            }

            $nominals[$ploting->id] = $nominal;
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        if (empty($nominals)) {
            throw ValidationException::withMessages([
                'nominal_hitung' => 'Minimal satu nominal hitung ploting wajib lebih dari Rp 0.',
            ]);
        }

        return $nominals;
    }

    private function plotingNominalPayload($plotings, $rows): array
    {
        $rowsByPloting = $rows->keyBy('plotingPremi_id');

        return $plotings
            ->map(function ($ploting) use ($rowsByPloting) {
                $row = $rowsByPloting->get($ploting->id);

                return [
                    'id' => (int) $ploting->id,
                    'kode' => $ploting->kode,
                    'ploting' => $ploting->ploting,
                    'text' => trim($ploting->kode.' - '.$ploting->ploting),
                    'nominal_hitung' => (int) ($row?->nominal_hitung ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function configPayload(string $jenisBhp, $configs): array
    {
        $configsByPloting = $configs->keyBy('plotingPremi_id');

        return [
            'jenis_bhp' => $jenisBhp,
            'jenis_bhp_label' => $this->typeLabel($jenisBhp),
            'nominal_defaults' => $this->generateBhpRepository->getPlotingPremi()
                ->map(function ($ploting) use ($configsByPloting) {
                    $config = $configsByPloting->get($ploting->id);

                    return [
                        'plotingPremi_id' => (int) $ploting->id,
                        'kode' => $ploting->kode,
                        'ploting' => $ploting->ploting,
                        'text' => trim($ploting->kode.' - '.$ploting->ploting),
                        'default_nominal' => (int) ($config?->default_nominal ?? 0),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function typeLabel(string $jenisBhp): string
    {
        return $jenisBhp === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
