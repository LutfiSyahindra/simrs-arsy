<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateBhpRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateBhpService
{
    public function __construct(
        protected generateBhpRepository $generateBhpRepository
    ) {
    }

    public function getResults(?string $periode = null, ?string $jenisBhp = null)
    {
        return $this->generateBhpRepository
            ->getResults($periode, $jenisBhp)
            ->map(fn ($row) => [
                'id' => $row->id,
                'periode' => $row->periode,
                'jenis_bhp' => $row->jenis_bhp,
                'jenis_bhp_label' => $this->typeLabel($row->jenis_bhp),
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
        $row = $periode
            ? $this->generateBhpRepository->findByPeriodAndType($periode, $jenisBhp)
            : null;

        return [
            'periode' => $periode,
            'jenis_bhp' => $jenisBhp,
            'jenis_bhp_label' => $this->typeLabel($jenisBhp),
            'jumlah_bhp' => $row?->jumlah_bhp ?? 0,
            'nominal_hitung' => $row?->nominal_hitung ?? 0,
            'total_bhp' => $row?->total_bhp ?? 0,
            'is_locked' => $row?->is_locked ?? false,
            'locked_at' => optional($row?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row?->lockedBy?->name,
        ];
    }

    public function generate(string $periode, string $jenisBhp, int $nominal): array
    {
        $typeLabel = $this->typeLabel($jenisBhp);

        return DB::transaction(function () use ($periode, $jenisBhp, $nominal, $typeLabel) {
            $existing = $this->generateBhpRepository
                ->findByPeriodAndTypeForUpdate($periode, $jenisBhp);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "BHP {$typeLabel} periode {$periode} sudah dikunci dan tidak dapat digenerate ulang.",
                ]);
            }

            $details = $this->generateBhpRepository->getEligibleByType($periode, $jenisBhp);

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => "Tidak ada data rawat inap {$typeLabel} yang memenuhi kriteria pada periode ini.",
                ]);
            }

            $result = $this->generateBhpRepository->saveResult(
                $periode,
                $jenisBhp,
                $details->count(),
                $nominal
            );

            $this->generateBhpRepository->replaceDetails($result, $details);

            return [
                'id' => $result->id,
                'periode' => $result->periode,
                'jenis_bhp' => $result->jenis_bhp,
                'jenis_bhp_label' => $this->typeLabel($result->jenis_bhp),
                'jumlah_bhp' => $result->jumlah_bhp,
                'nominal_hitung' => $result->nominal_hitung,
                'total_bhp' => $result->total_bhp,
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
            'jenis_bhp' => $result->jenis_bhp,
            'jenis_bhp_label' => $this->typeLabel($result->jenis_bhp),
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

    private function lockPayload($result): array
    {
        return [
            'id' => $result->id,
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
        ];
    }

    private function typeLabel(string $jenisBhp): string
    {
        return $jenisBhp === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
