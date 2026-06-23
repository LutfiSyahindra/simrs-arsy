<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateLaboratoriumRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateLaboratoriumService
{
    public function __construct(
        protected generateLaboratoriumRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisLaboratorium = null)
    {
        return $this->repository
            ->getResults($periode, $jenisLaboratorium)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisLaboratorium): array
    {
        return [
            'periode' => $periode,
            'jenis_laboratorium' => $jenisLaboratorium,
            'jenis_laboratorium_label' => $this->typeLabel($jenisLaboratorium),
            ...$this->repository->getSummary($periode, $jenisLaboratorium),
        ];
    }

    public function getConfig(string $jenisLaboratorium): array
    {
        return $this->configPayload($this->repository->getConfig($jenisLaboratorium));
    }

    public function updateConfig(string $jenisLaboratorium, array $data): array
    {
        $recipients = $this->hydrateRecipients($data['recipients'] ?? []);
        $payload = $this->configFormPayload($data, $jenisLaboratorium);

        return $this->configPayload(
            $this->repository->saveConfig($jenisLaboratorium, $payload, $recipients)
        );
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

    public function preview(string $periode, string $jenisLaboratorium): array
    {
        return $this->calculate($periode, $jenisLaboratorium);
    }

    public function generate(string $periode, string $jenisLaboratorium): array
    {
        return DB::transaction(function () use ($periode, $jenisLaboratorium) {
            $existing = $this->repository->findExistingForUpdate($periode, $jenisLaboratorium);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data laboratorium periode dan jenis ini sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($periode, $jenisLaboratorium, true);

            return $this->resultPayload(
                $this->repository->saveResult($periode, $jenisLaboratorium, $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate laboratorium tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $payload['source'] = [
            'jumlah_tindakan' => $result->jumlah_tindakan,
            'jumlah_pasien' => $result->jumlah_pasien,
            'total_bagian_laborat' => $result->total_bagian_laborat,
            'total_bagian_rs' => $result->total_bagian_rs,
            'total_manajemen' => $result->total_manajemen,
        ];
        $payload['pools'] = [
            'petugas' => $result->total_premi_petugas,
            'bersama' => $result->total_premi_bersama,
        ];
        $payload['total_tidak_dibagikan'] = max(
            0,
            $result->total_premi_petugas - $result->total_dibagikan
        );
        $payload['recipient_groups'] = collect($payload['details'])
            ->groupBy('role')
            ->map(fn ($items) => [
                'role' => $items->first()['role'],
                'role_label' => $items->first()['role_label'],
                'pool_total' => $items->first()['pool_total'],
                'recipient_count' => $items->count(),
                'total_received' => $items->sum('total_received'),
                'items' => $items->values(),
            ])
            ->values();

        return $payload;
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate laboratorium tidak ditemukan.');
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
                abort(404, 'Data generate laboratorium tidak ditemukan.');
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
                abort(404, 'Data generate laboratorium tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data laboratorium yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(string $periode, string $jenisLaboratorium, bool $strict = false): array
    {
        $config = $this->configPayload($this->repository->getConfig($jenisLaboratorium));
        $source = $this->repository->getSourceSummary($periode, $jenisLaboratorium);
        $petugasBase = $jenisLaboratorium === 'bpjs'
            ? 0
            : $source['total_bagian_laborat'];
        $bersamaBase = $jenisLaboratorium === 'bpjs'
            ? $source['total_bagian_rs']
            : $source['total_manajemen'];
        $bersama = $this->configuredAmount(
            $config['bersama_mode'],
            $bersamaBase,
            $config['bersama_percent'],
            $config['bersama_nominal']
        );
        $petugasBase = $jenisLaboratorium === 'bpjs' ? $bersama : $petugasBase;
        $petugas = $this->configuredPetugasAmount(
            $config['petugas_mode'],
            $petugasBase,
            $config['petugas_percent'],
            $config['petugas_nominal'],
            $config['bpjs_petugas_divider']
        );
        $recipients = $this->splitPetugasPool($petugas, $config['recipients'], $strict);
        $configSnapshot = [
            ...$config,
            'source_periode' => $source['source_periode'] ?? $periode,
            'source_tgl_awal' => $source['source_tgl_awal'] ?? null,
            'source_tgl_akhir' => $source['source_tgl_akhir'] ?? null,
            'petugas_base_label' => $jenisLaboratorium === 'bpjs'
                ? 'Premi bersama dibagi pembagi BPJS'
                : 'Total bagian_laborat',
            'bersama_base_label' => $jenisLaboratorium === 'bpjs'
                ? 'Total bagian_rs'
                : 'Total manajemen',
        ];

        return [
            'periode' => $periode,
            'jenis_laboratorium' => $jenisLaboratorium,
            'jenis_laboratorium_label' => $this->typeLabel($jenisLaboratorium),
            'source' => $source,
            'petugas_base' => $petugasBase,
            'bersama_base' => $bersamaBase,
            'source_periode' => $source['source_periode'] ?? $periode,
            'source_tgl_awal' => $source['source_tgl_awal'] ?? null,
            'source_tgl_akhir' => $source['source_tgl_akhir'] ?? null,
            'pools' => [
                'petugas' => $petugas,
                'bersama' => $bersama,
            ],
            'recipients' => $recipients,
            'total_dibagikan' => collect($recipients)->sum('total_received'),
            'config' => $configSnapshot,
            'config_snapshot' => $configSnapshot,
        ];
    }

    private function configuredAmount(string $mode, int $base, float $percent, int $nominal): int
    {
        return match ($mode) {
            'nominal' => $nominal,
            'source' => $base,
            default => (int) round($base * $percent / 100),
        };
    }

    private function configuredPetugasAmount(
        string $mode,
        int $base,
        float $percent,
        int $nominal,
        int $divider
    ): int {
        return match ($mode) {
            'nominal' => $nominal,
            'bersama_divider' => (int) round($base / max(1, $divider)),
            default => (int) round($base * $percent / 100),
        };
    }

    private function splitPetugasPool(int $pool, array $recipients, bool $strict): array
    {
        if ($strict && $pool > 0 && empty($recipients)) {
            throw ValidationException::withMessages([
                'recipients' => 'Pegawai penerima premi petugas laboratorium wajib dipilih sebelum generate.',
            ]);
        }

        if (empty($recipients)) {
            return [];
        }

        $allocationPercent = $pool > 0 ? 100 : null;

        return collect($recipients)->values()->map(function ($item) use ($pool, $allocationPercent) {
            return [
                'role' => 'petugas',
                'role_label' => 'Petugas Laboratorium',
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'] ?? null,
                'allocation_percent' => $allocationPercent,
                'pool_total' => $pool,
                'total_received' => $pool,
            ];
        })->all();
    }

    private function hydrateRecipients(array $input): array
    {
        return collect($input)
            ->filter()
            ->map(fn ($id) => trim((string) $id))
            ->unique()
            ->values()
            ->map(function ($id) {
                $row = $this->repository->findPegawai($id);

                if (! $row) {
                    throw ValidationException::withMessages([
                        'recipients' => 'Pegawai penerima premi petugas laboratorium tidak valid.',
                    ]);
                }

                return [
                    'pegawai_id' => $row->nik,
                    'pegawai_name' => $row->nama,
                    'pegawai_position' => $row->jbtn,
                ];
            })
            ->all();
    }

    private function configFormPayload(array $data, string $jenisLaboratorium): array
    {
        return [
            'petugas_mode' => $data['petugas_mode'] ?? 'percent',
            'petugas_percent' => (float) ($data['petugas_percent'] ?? 0),
            'petugas_nominal' => (int) ($data['petugas_nominal'] ?? 0),
            'bpjs_petugas_divider' => max(1, (int) ($data['bpjs_petugas_divider'] ?? 7)),
            'bersama_mode' => $data['bersama_mode'] ?? 'source',
            'bersama_percent' => (float) ($data['bersama_percent'] ?? 100),
            'bersama_nominal' => (int) ($data['bersama_nominal'] ?? 0),
        ];
    }

    private function configPayload($config): array
    {
        return [
            'id' => $config->id,
            'jenis_laboratorium' => $config->jenis_laboratorium,
            'jenis_laboratorium_label' => $this->typeLabel($config->jenis_laboratorium),
            'petugas_mode' => $config->petugas_mode,
            'petugas_percent' => (float) $config->petugas_percent,
            'petugas_nominal' => (int) $config->petugas_nominal,
            'bpjs_petugas_divider' => (int) ($config->bpjs_petugas_divider ?? 7),
            'bersama_mode' => $config->bersama_mode,
            'bersama_percent' => (float) $config->bersama_percent,
            'bersama_nominal' => (int) $config->bersama_nominal,
            'recipients' => $config->pegawai->map(fn ($pegawai) => [
                'pegawai_id' => $pegawai->pegawai_id,
                'pegawai_name' => $pegawai->pegawai_name,
                'pegawai_position' => $pegawai->pegawai_position,
                'text' => trim($pegawai->pegawai_id.' - '.$pegawai->pegawai_name),
            ])->values()->all(),
        ];
    }

    private function resultPayload($row): array
    {
        $configSnapshot = $row->config_snapshot ?: [];
        $sourcePeriode = $configSnapshot['source_periode']
            ?? $this->sourcePeriod($row->periode, $row->jenis_laboratorium);

        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'source_periode' => $sourcePeriode,
            'source_tgl_awal' => $configSnapshot['source_tgl_awal'] ?? null,
            'source_tgl_akhir' => $configSnapshot['source_tgl_akhir'] ?? null,
            'jenis_laboratorium' => $row->jenis_laboratorium,
            'jenis_laboratorium_label' => $this->typeLabel($row->jenis_laboratorium),
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'jumlah_pasien' => $row->jumlah_pasien,
            'total_bagian_laborat' => $row->total_bagian_laborat,
            'total_bagian_rs' => $row->total_bagian_rs,
            'total_manajemen' => $row->total_manajemen,
            'total_premi_petugas' => $row->total_premi_petugas,
            'total_premi_bersama' => $row->total_premi_bersama,
            'total_dibagikan' => $row->total_dibagikan,
            'details_count' => $row->details_count ?? $row->details?->count() ?? 0,
            'config_snapshot' => $row->config_snapshot,
            'details' => $row->relationLoaded('details')
                ? $row->details->map(fn ($detail) => [
                    'role' => $detail->role,
                    'role_label' => $detail->role_label,
                    'pegawai_id' => $detail->pegawai_id,
                    'pegawai_name' => $detail->pegawai_name,
                    'pegawai_position' => $detail->pegawai_position,
                    'allocation_percent' => $detail->allocation_percent,
                    'pool_total' => $detail->pool_total,
                    'total_received' => $detail->total_received,
                ])->values()
                : [],
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

    private function typeLabel(string $jenisLaboratorium): string
    {
        return $jenisLaboratorium === 'bpjs' ? 'BPJS Kesehatan' : 'Umum';
    }

    private function sourcePeriod(string $periode, string $jenisLaboratorium): string
    {
        if ($jenisLaboratorium !== 'bpjs') {
            return $periode;
        }

        return date('Y-m', strtotime($periode.'-01 -1 month'));
    }
}
