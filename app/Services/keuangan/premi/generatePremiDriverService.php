<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generatePremiDriverRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class generatePremiDriverService
{
    public function __construct(
        protected generatePremiDriverRepository $repository
    ) {}

    public function getResults(?string $periode = null)
    {
        return $this->repository
            ->getResults($periode)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode): array
    {
        return [
            'periode' => $periode,
            ...$this->repository->getSummary($periode),
            'config' => $this->getConfig(),
            'tujuan_count' => $this->repository->getTujuanList(true)->count(),
        ];
    }

    public function getConfig(): array
    {
        $config = $this->repository->getConfig();

        return [
            'id' => $config->id,
            'premi_bersama_percent' => (float) $config->premi_bersama_percent,
            'premi_pegawai_percent' => 100,
        ];
    }

    public function updateConfig(float $premiBersamaPercent): array
    {
        return [
            ...$this->configPayload(
                $this->repository->saveConfig($premiBersamaPercent)
            ),
            'premi_pegawai_percent' => 100,
        ];
    }

    public function tujuanList(?bool $active = null)
    {
        return $this->repository
            ->getTujuanList($active)
            ->map(fn ($row) => $this->tujuanPayload($row))
            ->values();
    }

    public function tujuanOptions()
    {
        return $this->tujuanList(true)
            ->map(fn ($row) => [
                ...$row,
                'text' => trim($row['kode'].' - '.$row['nama_tujuan'].' | Rp '.number_format($row['harga'], 0, ',', '.')),
            ])
            ->values();
    }

    public function saveTujuan(array $data, ?int $id = null): array
    {
        $kode = $this->normalizeKode($data['kode'] ?? null, $id);

        if ($this->repository->kodeTujuanExists($kode, $id)) {
            throw ValidationException::withMessages([
                'kode' => 'Kode tujuan sudah digunakan.',
            ]);
        }

        $payload = [
            'kode' => $kode,
            'nama_tujuan' => trim($data['nama_tujuan']),
            'harga' => (int) $data['harga'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? $this->nextSortOrder()),
        ];

        return $this->tujuanPayload(
            $this->repository->saveTujuan($payload, $id)
        );
    }

    public function deleteTujuan(int $id): void
    {
        $this->repository->deleteTujuan($id);
    }

    public function pegawaiOptions(?string $keyword = null)
    {
        return $this->repository
            ->searchPegawai($keyword)
            ->map(fn ($item) => [
                'id' => $item->nik,
                'nik' => $item->nik,
                'nama' => $item->nama,
                'jbtn' => $item->jbtn,
                'text' => trim($item->nik.' - '.$item->nama.' ('.($item->jbtn ?: '-').')'),
            ])
            ->values();
    }

    public function preview(string $pegawaiId, array $entries): array
    {
        $pegawai = $this->repository->findPegawai($pegawaiId);

        if (! $pegawai) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai penerima premi driver tidak ditemukan atau tidak aktif.',
            ]);
        }

        return $this->calculate($pegawai, $entries);
    }

    public function generate(string $periode, string $pegawaiId, array $entries): array
    {
        return DB::transaction(function () use ($periode, $pegawaiId, $entries) {
            $pegawai = $this->repository->findPegawai($pegawaiId);

            if (! $pegawai) {
                throw ValidationException::withMessages([
                    'pegawai_id' => 'Pegawai penerima premi driver tidak ditemukan atau tidak aktif.',
                ]);
            }

            $existing = $this->repository->findExistingForUpdate($periode, $pegawaiId);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'pegawai_id' => 'Premi driver pegawai ini pada periode tersebut sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($pegawai, $entries, true);

            return $this->resultPayload(
                $this->repository->saveResult($periode, $pegawai, $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate premi driver tidak ditemukan.');
        }

        return $this->resultPayload($result);
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate premi driver tidak ditemukan.');
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
                abort(404, 'Data generate premi driver tidak ditemukan.');
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

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate premi driver tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data premi driver yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(object $pegawai, array $entries, bool $strict = false): array
    {
        if (empty($entries)) {
            throw ValidationException::withMessages([
                'entries' => 'Minimal pilih satu tujuan ambulance.',
            ]);
        }

        $seen = [];
        $details = [];

        foreach ($entries as $index => $entry) {
            $tujuanId = (int) ($entry['tujuan_id'] ?? 0);
            $jumlah = (int) ($entry['jumlah'] ?? 0);

            if ($jumlah < 1) {
                throw ValidationException::withMessages([
                    "entries.{$index}.jumlah" => 'Jumlah tujuan minimal 1.',
                ]);
            }

            if (isset($seen[$tujuanId])) {
                throw ValidationException::withMessages([
                    "entries.{$index}.tujuan_id" => 'Tujuan tidak boleh duplikat dalam satu generate.',
                ]);
            }

            $seen[$tujuanId] = true;
            $tujuan = $this->repository->findActiveTujuan($tujuanId);

            if (! $tujuan) {
                throw ValidationException::withMessages([
                    "entries.{$index}.tujuan_id" => 'Tujuan ambulance tidak ditemukan atau sedang nonaktif.',
                ]);
            }

            $details[] = [
                'tujuan_id' => $tujuan->id,
                'kode_tujuan' => $tujuan->kode,
                'nama_tujuan' => $tujuan->nama_tujuan,
                'harga' => (int) $tujuan->harga,
                'jumlah' => $jumlah,
                'subtotal' => (int) $tujuan->harga * $jumlah,
            ];
        }

        $grandTotal = collect($details)->sum('subtotal');
        $config = $this->getConfig();
        $premiBersamaPercent = (float) $config['premi_bersama_percent'];
        $premiPegawaiPercent = 100;
        $premiBersama = (int) round($grandTotal * $premiBersamaPercent / 100);
        $premiPegawai = $grandTotal;

        if ($strict && $grandTotal <= 0) {
            throw ValidationException::withMessages([
                'entries' => 'Grand total harus lebih dari Rp 0.',
            ]);
        }

        return [
            'pegawai' => [
                'pegawai_id' => $pegawai->nik,
                'pegawai_name' => $pegawai->nama,
                'pegawai_position' => $pegawai->jbtn,
            ],
            'jumlah_tujuan' => count($details),
            'total_jumlah' => collect($details)->sum('jumlah'),
            'grand_total' => $grandTotal,
            'premi_pegawai_percent' => $premiPegawaiPercent,
            'total_premi_pegawai' => $premiPegawai,
            'premi_bersama_percent' => $premiBersamaPercent,
            'total_premi_bersama' => $premiBersama,
            'details' => $details,
            'config_snapshot' => [
                'premi_bersama_percent' => $premiBersamaPercent,
                'premi_pegawai_percent' => $premiPegawaiPercent,
                'formula' => 'subtotal = jumlah x harga tujuan; premi pegawai = 100% grand total; premi bersama = grand total x persen bersama',
                'tujuan' => $details,
            ],
        ];
    }

    private function resultPayload($row): array
    {
        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'pegawai_id' => $row->pegawai_id,
            'pegawai_name' => $row->pegawai_name,
            'pegawai_position' => $row->pegawai_position,
            'pegawai_label' => trim($row->pegawai_id.' - '.$row->pegawai_name),
            'jumlah_tujuan' => $row->jumlah_tujuan,
            'total_jumlah' => $row->total_jumlah,
            'grand_total' => $row->grand_total,
            'premi_pegawai_percent' => (float) $row->premi_pegawai_percent,
            'total_premi_pegawai' => $row->total_premi_pegawai,
            'premi_bersama_percent' => (float) $row->premi_bersama_percent,
            'total_premi_bersama' => $row->total_premi_bersama,
            'details_count' => $row->details_count ?? $row->details?->count() ?? 0,
            'details' => $row->relationLoaded('details')
                ? $row->details->map(fn ($detail) => [
                    'id' => $detail->id,
                    'tujuan_id' => $detail->tujuan_id,
                    'kode_tujuan' => $detail->kode_tujuan,
                    'nama_tujuan' => $detail->nama_tujuan,
                    'harga' => $detail->harga,
                    'jumlah' => $detail->jumlah,
                    'subtotal' => $detail->subtotal,
                ])->values()
                : [],
            'config_snapshot' => $row->config_snapshot,
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

    private function tujuanPayload($row): array
    {
        return [
            'id' => $row->id,
            'kode' => $row->kode,
            'nama_tujuan' => $row->nama_tujuan,
            'harga' => $row->harga,
            'is_active' => $row->is_active,
            'sort_order' => $row->sort_order,
        ];
    }

    private function configPayload($config): array
    {
        return [
            'id' => $config->id,
            'premi_bersama_percent' => (float) $config->premi_bersama_percent,
        ];
    }

    private function normalizeKode(?string $kode, ?int $id = null): string
    {
        $kode = trim((string) $kode);

        if ($kode !== '') {
            return Str::upper($kode);
        }

        if ($id && $tujuan = $this->repository->findTujuan($id)) {
            return $tujuan->kode;
        }

        return $this->nextKode();
    }

    private function nextKode(): string
    {
        $numbers = $this->repository->getTujuanList()
            ->map(function ($tujuan) {
                preg_match('/(\d+)$/', $tujuan->kode, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            });

        return 'AMB'.str_pad((string) ($numbers->max() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function nextSortOrder(): int
    {
        return ((int) $this->repository->getTujuanList()->max('sort_order')) + 1;
    }
}
