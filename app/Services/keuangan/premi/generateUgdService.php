<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateUgdRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateUgdService
{
    public function __construct(
        protected generateUgdRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisUgd = null)
    {
        return $this->repository
            ->getResults($periode, $jenisUgd)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisUgd): array
    {
        return [
            'periode' => $periode,
            'jenis_ugd' => $jenisUgd,
            'jenis_ugd_label' => $this->typeLabel($jenisUgd),
            ...$this->repository->getSummary($periode, $jenisUgd),
        ];
    }

    public function getDokterOptions(?string $keyword = null)
    {
        return $this->repository
            ->getDokterOptions($keyword)
            ->map(fn ($item) => [
                'id' => $item->kd_dokter,
                'kd_dokter' => $item->kd_dokter,
                'nm_dokter' => $item->nm_dokter,
                'text' => trim($item->kd_dokter.' - '.$item->nm_dokter),
            ])
            ->values();
    }

    public function getPlotingOptions()
    {
        return $this->repository
            ->getPlotingPremi()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'ploting' => $item->ploting,
                'text' => trim($item->kode.' - '.$item->ploting),
            ])
            ->values();
    }

    public function copyPreview(string $periode, string $jenisUgd): array
    {
        $targetPeriode = Carbon::createFromFormat('Y-m-d', $periode.'-01')
            ->addMonthNoOverflow()
            ->format('Y-m');
        $items = $this->repository
            ->getResults($periode, $jenisUgd)
            ->map(fn ($row) => $this->resultPayload($row))
            ->values();

        return [
            'source_periode' => $periode,
            'target_periode' => $targetPeriode,
            'jenis_ugd' => $jenisUgd,
            'jenis_ugd_label' => $this->typeLabel($jenisUgd),
            'count' => $items->count(),
            'jumlah_pasien' => $items->sum('jumlah_pasien'),
            'total_ugd' => $items->sum('total_ugd'),
            'items' => $items,
        ];
    }

    public function generate(
        string $periode,
        string $jenisUgd,
        string $kdDokter,
        int $plotingId,
        int $jumlahPasien,
        int $nominal
    ): array {
        return DB::transaction(
            fn () => $this->generateRow(
                $periode,
                $jenisUgd,
                $kdDokter,
                $plotingId,
                $jumlahPasien,
                $nominal
            )
        );
    }

    public function generateMany(
        string $periode,
        string $jenisUgd,
        array $entries
    ): array {
        return DB::transaction(function () use (
            $periode,
            $jenisUgd,
            $entries
        ) {
            $results = [];
            $seen = [];

            foreach ($entries as $index => $entry) {
                $kdDokter = (string) $entry['kd_dokter'];
                $plotingId = (int) $entry['plotingPremi_id'];
                $key = $kdDokter.'|'.$plotingId;

                if (isset($seen[$key])) {
                    throw ValidationException::withMessages([
                        "entries.{$index}.kd_dokter" => 'Dokter dan ploting tidak boleh duplikat dalam satu generate.',
                    ]);
                }

                $seen[$key] = true;
                $results[] = $this->generateRow(
                    $periode,
                    $jenisUgd,
                    $kdDokter,
                    $plotingId,
                    (int) $entry['jumlah_pasien'],
                    (int) $entry['nominal_hitung'],
                    "entries.{$index}."
                );
            }

            return [
                'periode' => $periode,
                'jenis_ugd' => $jenisUgd,
                'jenis_ugd_label' => $this->typeLabel($jenisUgd),
                'count' => count($results),
                'jumlah_pasien' => collect($results)->sum('jumlah_pasien'),
                'total_ugd' => collect($results)->sum('total_ugd'),
                'items' => $results,
            ];
        });
    }

    private function generateRow(
        string $periode,
        string $jenisUgd,
        string $kdDokter,
        int $plotingId,
        int $jumlahPasien,
        int $nominal,
        string $errorPrefix = ''
    ): array {
        $dokter = $this->repository->findDokter($kdDokter);

        if (! $dokter) {
            throw ValidationException::withMessages([
                $errorPrefix.'kd_dokter' => 'Dokter tidak ditemukan di data Khanza.',
            ]);
        }

        $ploting = $this->repository->findPloting($plotingId);

        if (! $ploting) {
            throw ValidationException::withMessages([
                $errorPrefix.'plotingPremi_id' => 'Ploting premi tidak ditemukan.',
            ]);
        }

        $existing = $this->repository->findExistingForUpdate(
            $periode,
            $jenisUgd,
            $kdDokter,
            $plotingId
        );

        if ($existing?->is_locked) {
            throw ValidationException::withMessages([
                $errorPrefix.'kd_dokter' => 'Data UGD dokter dan ploting ini sudah dikunci.',
            ]);
        }

        return $this->resultPayload(
            $this->repository->saveResult(
                $periode,
                $jenisUgd,
                $dokter,
                $ploting,
                $jumlahPasien,
                $nominal
            )->fresh(['lockedBy:id,name', 'generateBy:id,name'])
        );
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate UGD tidak ditemukan.');
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
                abort(404, 'Data generate UGD tidak ditemukan.');
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

    private function resultPayload($row): array
    {
        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'jenis_ugd' => $row->jenis_ugd,
            'jenis_ugd_label' => $this->typeLabel($row->jenis_ugd),
            'kd_dokter' => $row->kd_dokter,
            'nm_dokter' => $row->nm_dokter,
            'plotingPremi_id' => $row->plotingPremi_id,
            'kode_ploting' => $row->kode_ploting,
            'nama_ploting' => $row->nama_ploting,
            'ploting_label' => trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-')),
            'jumlah_pasien' => $row->jumlah_pasien,
            'nominal_hitung' => $row->nominal_hitung,
            'total_ugd' => $row->total_ugd,
            'is_locked' => $row->is_locked,
            'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row->lockedBy?->name,
            'generate_by_name' => $row->generateBy?->name,
            'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
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

    private function typeLabel(string $jenisUgd): string
    {
        return $jenisUgd === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
