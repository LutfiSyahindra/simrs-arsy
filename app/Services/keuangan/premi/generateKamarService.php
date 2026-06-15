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
        $row = $periode
            ? $this->generateKamarRepository->findByPeriodAndType($periode, $jenisKamar)
            : null;

        return [
            'periode' => $periode,
            'periode_sumber' => $periode
                ? PremiSourcePeriod::resolve($periode, $jenisKamar)
                : null,
            'jenis_kamar' => $jenisKamar,
            'jenis_kamar_label' => $this->typeLabel($jenisKamar),
            'jumlah_kamar' => $row?->jumlah_kamar ?? 0,
            'jumlah_lama_inap' => $row?->jumlah_lama_inap ?? 0,
            'nominal_hitung' => $row?->nominal_hitung ?? 0,
            'total_lama_inap' => $row?->total_lama_inap ?? 0,
            'is_locked' => $row?->is_locked ?? false,
            'locked_at' => optional($row?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row?->lockedBy?->name,
        ];
    }

    public function generate(string $periode, string $jenisKamar, int $nominal): array
    {
        $typeLabel = $this->typeLabel($jenisKamar);
        $sourcePeriod = PremiSourcePeriod::resolve($periode, $jenisKamar);

        return DB::transaction(function () use (
            $periode,
            $jenisKamar,
            $nominal,
            $typeLabel,
            $sourcePeriod
        ) {
            $existing = $this->generateKamarRepository
                ->findByPeriodAndTypeForUpdate($periode, $jenisKamar);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Kamar {$typeLabel} periode {$periode} sudah dikunci dan tidak dapat digenerate ulang.",
                ]);
            }

            $details = $this->generateKamarRepository->getEligibleByType($periode, $jenisKamar);

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => "Tidak ada data kamar inap {$typeLabel} yang memenuhi kriteria pada periode sumber {$sourcePeriod}.",
                ]);
            }

            $jumlahLamaInap = (int) $details->sum('lama');
            $result = $this->generateKamarRepository->saveResult(
                $periode,
                $jenisKamar,
                $details->count(),
                $jumlahLamaInap,
                $nominal
            );

            $this->generateKamarRepository->replaceDetails($result, $details);

            return $this->resultPayload($result);
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

    private function typeLabel(string $jenisKamar): string
    {
        return $jenisKamar === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
