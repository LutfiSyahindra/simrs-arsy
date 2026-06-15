<?php

namespace App\Services\keuangan\premi;

use App\Models\dbSimrs\premiPelayananNonMedisModel;
use App\Models\User;
use App\Repositories\keuangan\premi\generatePelayananNonMedisRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generatePelayananNonMedisService
{
    public function __construct(
        protected generatePelayananNonMedisRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenis = null)
    {
        return $this->repository
            ->getResults($periode, $jenis)
            ->map(fn ($row) => [
                ...$this->resultPayload($row),
                'jumlah_detail' => $row->details_count,
                'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
                'is_locked' => $row->is_locked,
                'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
                'locked_by_name' => $row->lockedBy?->name,
                'generate_by_name' => $row->generateBy?->name,
            ]);
    }

    public function getSummary(string $periode, string $jenis): array
    {
        $dependencies = $this->repository->getDependencies($periode, $jenis);
        $existing = $this->repository->findByPeriodAndType($periode, $jenis);
        $calculation = null;
        $dependenciesReady = $this->dependenciesAreLocked($dependencies);

        if ($dependenciesReady && ! $existing?->is_locked) {
            $calculation = $this->repository->calculate($periode, $jenis);
        }

        $totalBhp = $calculation
            ? $dependencies['bhp']->total_bhp
            : ($existing?->total_bhp ?? $dependencies['bhp']?->total_bhp ?? 0);
        $totalKamar = $calculation
            ? $dependencies['kamar']->total_lama_inap
            : ($existing?->total_kamar_inap ?? $dependencies['kamar']?->total_lama_inap ?? 0);
        $totalMapping = $calculation['total_mapping_premi']
            ?? $existing?->total_mapping_premi
            ?? 0;

        return [
            'periode' => $periode,
            'periode_sumber' => PremiSourcePeriod::resolve($periode, $jenis),
            'jenis_pelayanan' => $jenis,
            'jenis_pelayanan_label' => $this->typeLabel($jenis),
            'dependency_bhp' => $this->dependencyPayload(
                $dependencies['bhp'],
                'total_bhp'
            ),
            'dependency_kamar' => $this->dependencyPayload(
                $dependencies['kamar'],
                'total_lama_inap'
            ),
            'ready' => $dependenciesReady,
            'readiness_message' => $this->readinessMessage($dependencies),
            'is_generated' => (bool) $existing,
            'jumlah_transaksi' => $calculation['jumlah_transaksi']
                ?? $existing?->jumlah_transaksi
                ?? 0,
            'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan']
                ?? $existing?->jumlah_jenis_tindakan
                ?? 0,
            'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi']
                ?? $existing?->jumlah_mapping_premi
                ?? 0,
            'total_biaya_rawat' => $calculation['total_biaya_rawat']
                ?? $existing?->total_biaya_rawat
                ?? 0,
            'total_mapping_premi' => $totalMapping,
            'total_bhp' => $totalBhp,
            'total_kamar_inap' => $totalKamar,
            'total_final' => $calculation
                ? round(
                    (float) $totalMapping
                    + (float) $totalBhp
                    + (float) $totalKamar,
                    2
                )
                : ($existing?->total_final ?? 0),
            'is_locked' => $existing?->is_locked ?? false,
            'locked_at' => optional($existing?->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $existing?->lockedBy?->name,
        ];
    }

    public function generate(string $periode, string $jenis): array
    {
        return DB::transaction(function () use ($periode, $jenis) {
            $existing = $this->repository
                ->findByPeriodAndTypeForUpdate($periode, $jenis);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Pelayanan Non Medis {$this->typeLabel($jenis)} periode {$periode} sudah dikunci.",
                ]);
            }

            $dependencies = $this->repository->getDependencies(
                $periode,
                $jenis,
                true
            );
            $missing = collect([
                'BHP' => $dependencies['bhp'],
                'Kamar Inap' => $dependencies['kamar'],
            ])->filter(fn ($value) => ! $value)->keys();

            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => 'Generate terlebih dahulu: '.$missing->implode(' dan ').'.',
                ]);
            }

            $unlocked = collect([
                'BHP' => $dependencies['bhp'],
                'Kamar Inap' => $dependencies['kamar'],
            ])->filter(fn ($value) => ! $value->is_locked)->keys();

            if ($unlocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'periode' => 'Kunci terlebih dahulu: '.$unlocked->implode(' dan ').'.',
                ]);
            }

            $calculation = $this->repository->calculate($periode, $jenis);

            if ($calculation['jumlah_mapping_premi'] === 0) {
                $sourcePeriod = PremiSourcePeriod::resolve($periode, $jenis);

                throw ValidationException::withMessages([
                    'mapping' => "Tidak ada transaksi periode sumber {$sourcePeriod} yang sesuai dengan mapping tindakan dan mapping premi.",
                ]);
            }

            $result = $this->repository->saveResult(
                $periode,
                $jenis,
                $dependencies,
                $calculation
            );
            $this->repository->replaceDetails($result, $calculation['details']);

            return $this->resultPayload($result->fresh());
        });
    }

    public function getDetail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data premi pelayanan non medis tidak ditemukan.');
        }

        return [
            ...$this->resultPayload($result),
            'is_locked' => $result->is_locked,
            'locked_at' => optional($result->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $result->lockedBy?->name,
            'generate_by_name' => $result->generateBy?->name,
            'details' => $result->details->map(fn ($detail) => [
                'id' => $detail->id,
                'mapping_premi_id' => $detail->mapping_premi_id,
                'jnsPremi_id' => $detail->jnsPremi_id,
                'jnsTindakan_id' => $detail->jnsTindakan_id,
                'kode_premi' => $detail->kode_premi,
                'nama_premi' => $detail->nama_premi,
                'kode_jenis_tindakan' => $detail->kode_jenis_tindakan,
                'nama_jenis_tindakan' => $detail->nama_jenis_tindakan,
                'jenis_mapping' => $detail->jenis_mapping,
                'nilai_mapping' => $detail->nilai_mapping,
                'jumlah_data' => $detail->jumlah_data,
                'total_biaya_rawat' => $detail->total_biaya_rawat,
                'dasar_hitung' => $detail->dasar_hitung,
                'hasil_mapping' => $detail->hasil_mapping,
                'data_tindakan' => $detail->data_tindakan,
            ])->values(),
        ];
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data premi pelayanan non medis tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data sudah dalam keadaan terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, true, $user->id)
            );
        });
    }

    public function unlock(int $id, User $user): array
    {
        if (! $user->hasRole('Admin')) {
            throw new AuthorizationException(
                'Hanya user dengan role Admin yang dapat membuka kunci data.'
            );
        }

        return DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data premi pelayanan non medis tidak ditemukan.');
            }

            if (! $result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data tidak sedang terkunci.',
                ]);
            }

            return $this->lockPayload(
                $this->repository->updateLock($result, false)
            );
        });
    }

    private function resultPayload(premiPelayananNonMedisModel $result): array
    {
        return [
            'id' => $result->id,
            'periode' => $result->periode,
            'periode_sumber' => PremiSourcePeriod::resolve(
                $result->periode,
                $result->jenis_pelayanan
            ),
            'jenis_pelayanan' => $result->jenis_pelayanan,
            'jenis_pelayanan_label' => $this->typeLabel($result->jenis_pelayanan),
            'jumlah_transaksi' => $result->jumlah_transaksi,
            'jumlah_jenis_tindakan' => $result->jumlah_jenis_tindakan,
            'jumlah_mapping_premi' => $result->jumlah_mapping_premi,
            'total_biaya_rawat' => $result->total_biaya_rawat,
            'total_mapping_premi' => $result->total_mapping_premi,
            'total_bhp' => $result->total_bhp,
            'total_kamar_inap' => $result->total_kamar_inap,
            'total_final' => $result->total_final,
        ];
    }

    private function dependencyPayload($row, string $totalField): array
    {
        return [
            'exists' => (bool) $row,
            'id' => $row?->id,
            'total' => $row?->{$totalField} ?? 0,
            'is_locked' => $row?->is_locked ?? false,
            'updated_at' => optional($row?->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function dependenciesAreLocked(array $dependencies): bool
    {
        return (bool) (
            $dependencies['bhp']?->is_locked
            && $dependencies['kamar']?->is_locked
        );
    }

    private function readinessMessage(array $dependencies): string
    {
        $missing = collect([
            'BHP' => $dependencies['bhp'],
            'Kamar Inap' => $dependencies['kamar'],
        ])->filter(fn ($value) => ! $value)->keys();

        if ($missing->isNotEmpty()) {
            return 'Generate terlebih dahulu: '.$missing->implode(' dan ').'.';
        }

        $unlocked = collect([
            'BHP' => $dependencies['bhp'],
            'Kamar Inap' => $dependencies['kamar'],
        ])->filter(fn ($value) => ! $value->is_locked)->keys();

        if ($unlocked->isNotEmpty()) {
            return 'Kunci terlebih dahulu: '.$unlocked->implode(' dan ').'.';
        }

        return 'Data BHP dan Kamar Inap sudah tersedia dan terkunci.';
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

    private function typeLabel(string $jenis): string
    {
        return $jenis === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
