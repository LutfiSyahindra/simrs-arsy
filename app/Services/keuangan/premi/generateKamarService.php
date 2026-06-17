<?php

namespace App\Services\keuangan\premi;

use App\Models\dbSimrs\generateKamarModel;
use App\Models\User;
use App\Repositories\keuangan\premi\generateKamarRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateKamarService
{
    public function __construct(
        protected generateKamarRepository $generateKamarRepository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisKamar = null)
    {
        return $this->generateKamarRepository
            ->getResults($periode, $jenisKamar)
            ->map(fn ($row) => [
                'id' => $row->id,
                'periode' => $row->periode,
                'periode_sumber' => PremiSourcePeriod::resolve(
                    $row->periode,
                    $row->jenis_kamar
                ),
                'jenis_kamar' => $row->jenis_kamar,
                'jenis_kamar_label' => $this->typeLabel($row->jenis_kamar),
                'plotingPremi_id' => $row->plotingPremi_id,
                'kode_ploting' => $row->kode_ploting,
                'nama_ploting' => $row->nama_ploting,
                'ploting_label' => trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-')),
                'jumlah_kamar' => $row->jumlah_kamar,
                'jumlah_lama_inap' => $row->jumlah_lama_inap,
                'nominal_hitung' => $row->nominal_hitung,
                'total_lama_inap' => $row->total_lama_inap,
                'jumlah_detail' => $row->details_count,
                'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
                'is_locked' => $row->is_locked,
                'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
                'locked_by_name' => $row->lockedBy?->name,
                'generate_by_name' => $row->generateBy?->name,
            ]);
    }

    public function getSummary(?string $periode, string $jenisKamar): array
    {
        $rows = $periode
            ? $this->generateKamarRepository->findByPeriodAndType($periode, $jenisKamar)
            : collect();
        $firstRow = $rows->first();
        $lockedRows = $rows->where('is_locked', true);
        $lastLockedRow = $lockedRows->sortByDesc('locked_at')->first();
        $plotings = $this->generateKamarRepository->getPlotingPremi();
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
                ? PremiSourcePeriod::resolve($periode, $jenisKamar)
                : null,
            'jenis_kamar' => $jenisKamar,
            'jenis_kamar_label' => $this->typeLabel($jenisKamar),
            'jumlah_kamar' => $firstRow?->jumlah_kamar ?? 0,
            'jumlah_lama_inap' => $firstRow?->jumlah_lama_inap ?? 0,
            'nominal_hitung' => $firstRow?->nominal_hitung ?? 0,
            'nominal_is_mixed' => $nominals->count() > 1,
            'total_lama_inap' => $rows->sum('total_lama_inap'),
            'ploting_count' => $plotingCount,
            'generated_ploting_count' => $rows->count(),
            'locked_ploting_count' => $lockedRows->count(),
            'is_locked' => $lockedRows->isNotEmpty(),
            'locked_at' => optional($lastLockedRow?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $lastLockedRow?->lockedBy?->name,
            'ploting_nominals' => $this->plotingNominalPayload($plotings, $rows),
        ];
    }

    public function generate(string $periode, string $jenisKamar, array $nominalByPloting): array
    {
        $typeLabel = $this->typeLabel($jenisKamar);
        $sourcePeriod = PremiSourcePeriod::resolve($periode, $jenisKamar);

        return DB::transaction(function () use (
            $periode,
            $jenisKamar,
            $nominalByPloting,
            $typeLabel,
            $sourcePeriod
        ) {
            $plotings = $this->generateKamarRepository->getPlotingPremi();

            if ($plotings->isEmpty()) {
                throw ValidationException::withMessages([
                    'ploting' => 'Master Ploting Premi belum tersedia. Tambahkan data ploting terlebih dahulu.',
                ]);
            }

            $nominals = $this->normalizeNominals($plotings, $nominalByPloting);
            $existingRows = $this->generateKamarRepository
                ->findByPeriodAndTypeForUpdate($periode, $jenisKamar);
            $lockedRows = $existingRows->where('is_locked', true);

            if ($lockedRows->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => "Kamar {$typeLabel} periode {$periode} memiliki {$lockedRows->count()} ploting yang sudah dikunci dan tidak dapat digenerate ulang.",
                ]);
            }

            $details = $this->generateKamarRepository->getEligibleByType($periode, $jenisKamar);

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => "Tidak ada data kamar inap {$typeLabel} yang memenuhi kriteria pada periode sumber {$sourcePeriod}.",
                ]);
            }

            $jumlahLamaInap = (int) $details->sum('lama');
            $results = $plotings->map(function ($ploting) use (
                $periode,
                $jenisKamar,
                $details,
                $jumlahLamaInap,
                $nominals
            ) {
                $nominal = $nominals[$ploting->id];
                $result = $this->generateKamarRepository->saveResult(
                    $periode,
                    $jenisKamar,
                    $ploting,
                    $details->count(),
                    $jumlahLamaInap,
                    $nominal
                );

                $this->generateKamarRepository->replaceDetails($result, $details);

                return $result;
            });
            $firstResult = $results->first();

            return [
                ...$this->resultPayload($firstResult),
                'total_lama_inap' => $results->sum('total_lama_inap'),
                'nominal_is_mixed' => collect($nominals)->unique()->count() > 1,
                'ploting_count' => $plotings->count(),
                'generated_count' => $results->count(),
            ];
        });
    }

    public function getDetail(int $id): array
    {
        $result = $this->generateKamarRepository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate kamar tidak ditemukan.');
        }

        $penjaminNames = $this->generateKamarRepository->getPenjaminNames(
            $result->details->pluck('kd_pj')
        );

        return [
            ...$this->resultPayload($result),
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'details' => $result->details->map(fn ($detail) => [
                'no_rawat' => $detail->no_rawat,
                'tgl_masuk' => $detail->tgl_masuk?->format('d-m-Y'),
                'jam_masuk' => $detail->jam_masuk,
                'kd_pj' => $detail->kd_pj,
                'nama_penjamin' => $detail->nama_penjamin
                    ?: ($penjaminNames[$detail->kd_pj] ?? $detail->kd_pj),
                'kd_kamar' => $detail->kd_kamar,
                'lama' => $detail->lama,
            ])->values(),
        ];
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->generateKamarRepository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate kamar tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data kamar sudah dalam keadaan terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->generateKamarRepository->updateLock($result, true, $user->id)
            );
        });
    }

    public function unlock(int $id, User $user): array
    {
        if (! $user->hasRole('Admin')) {
            throw new AuthorizationException(
                'Hanya user dengan role Admin yang dapat membuka kunci data kamar.'
            );
        }

        return DB::transaction(function () use ($id) {
            $result = $this->generateKamarRepository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate kamar tidak ditemukan.');
            }

            if (! $result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data kamar tidak sedang terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->generateKamarRepository->updateLock($result, false)
            );
        });
    }

    private function resultPayload(generateKamarModel $result): array
    {
        return [
            'id' => $result->id,
            'periode' => $result->periode,
            'periode_sumber' => PremiSourcePeriod::resolve(
                $result->periode,
                $result->jenis_kamar
            ),
            'jenis_kamar' => $result->jenis_kamar,
            'jenis_kamar_label' => $this->typeLabel($result->jenis_kamar),
            'plotingPremi_id' => $result->plotingPremi_id,
            'kode_ploting' => $result->kode_ploting,
            'nama_ploting' => $result->nama_ploting,
            'ploting_label' => trim(($result->kode_ploting ? $result->kode_ploting.' - ' : '').($result->nama_ploting ?? '-')),
            'jumlah_kamar' => $result->jumlah_kamar,
            'jumlah_lama_inap' => $result->jumlah_lama_inap,
            'nominal_hitung' => $result->nominal_hitung,
            'total_lama_inap' => $result->total_lama_inap,
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
                $errors["nominal_hitung.{$ploting->id}"] =
                    "Nominal hitung {$ploting->ploting} wajib lebih dari Rp 0.";

                continue;
            }

            $nominals[$ploting->id] = $nominal;
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
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

    private function typeLabel(string $jenisKamar): string
    {
        return $jenisKamar === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
