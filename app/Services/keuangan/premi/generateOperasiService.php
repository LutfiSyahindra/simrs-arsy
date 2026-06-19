<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateOperasiRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateOperasiService
{
    private const ROLE_SOURCES = [
        'instrumen_20' => 'pegawai',
        'instrumen_80' => 'pegawai',
        'dokter_anastesi' => 'dokter',
        'perawat_anastesi' => 'pegawai',
    ];

    private const ROLE_LABELS = [
        'instrumen_20' => 'Petugas Instrumen 20%',
        'instrumen_80' => 'Petugas Instrumen 80%',
        'dokter_anastesi' => 'Dokter Anastesi',
        'perawat_anastesi' => 'Perawat Anastesi',
    ];

    public function __construct(
        protected generateOperasiRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisOperasi = null)
    {
        return $this->repository
            ->getResults($periode, $jenisOperasi)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisOperasi): array
    {
        return [
            'periode' => $periode,
            'jenis_operasi' => $jenisOperasi,
            'jenis_operasi_label' => $this->typeLabel($jenisOperasi),
            ...$this->repository->getSummary($periode, $jenisOperasi),
        ];
    }

    public function getConfig(string $jenisOperasi): array
    {
        return $this->configPayload($this->repository->getConfig($jenisOperasi));
    }

    public function updateConfig(string $jenisOperasi, array $data): array
    {
        $recipients = $this->hydrateRecipients($data['recipients'] ?? []);
        $percentages = $this->percentagePayload($data);

        return $this->configPayload(
            $this->repository->saveConfig($jenisOperasi, $percentages, $recipients)
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

    public function dokterOptions(?string $keyword = null)
    {
        return $this->repository
            ->searchDokter($keyword)
            ->map(fn ($item) => [
                'id' => $item->kd_dokter,
                'kd_dokter' => $item->kd_dokter,
                'nm_dokter' => $item->nm_dokter,
                'text' => trim($item->kd_dokter.' - '.$item->nm_dokter),
            ])
            ->values();
    }

    public function preview(string $jenisOperasi, int $totalOperasi): array
    {
        return $this->calculate($jenisOperasi, $totalOperasi);
    }

    public function generate(string $periode, string $jenisOperasi, int $totalOperasi): array
    {
        return DB::transaction(function () use ($periode, $jenisOperasi, $totalOperasi) {
            $existing = $this->repository->findExistingForUpdate($periode, $jenisOperasi);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data operasi periode dan jenis ini sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($jenisOperasi, $totalOperasi, true);

            return $this->resultPayload(
                $this->repository->saveResult($periode, $jenisOperasi, $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate operasi tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $config = $result->config_snapshot ?: [];
        $configPercentages = $config['percentages'] ?? [];
        $percentages = $this->percentagePayload($configPercentages);
        $details = collect($payload['details']);
        $anastesiPool = $this->portion($result->total_operasi, $percentages['dokter_anastesi_percent']);
        $perawatAnastesiPool = $this->portion($anastesiPool, $percentages['perawat_anastesi_percent']);
        $hasPerawatSplit = array_key_exists('perawat_anastesi_petugas_percent', $configPercentages)
            || array_key_exists('perawat_anastesi_premi_bersama_percent', $configPercentages);
        $perawatAnastesiPremiBersama = $hasPerawatSplit
            ? $this->portion($perawatAnastesiPool, $percentages['perawat_anastesi_premi_bersama_percent'])
            : 0;
        $premiBersamaInstrumen = max(0, $result->total_premi_bersama - $perawatAnastesiPremiBersama);

        $payload['config_snapshot'] = [
            'jenis_operasi' => $config['jenis_operasi'] ?? $result->jenis_operasi,
            'jenis_operasi_label' => $config['jenis_operasi_label'] ?? $this->typeLabel($result->jenis_operasi),
            'percentages' => $percentages,
            'recipient_counts' => $details
                ->groupBy('role')
                ->map(fn ($items) => $items->count())
                ->toArray(),
            'role_labels' => self::ROLE_LABELS,
        ];
        $payload['pools'] = [
            'instrumen' => $result->total_instrumen,
            'premi_bersama' => $result->total_premi_bersama,
            'premi_bersama_instrumen' => $premiBersamaInstrumen,
            'instrumen_petugas' => $result->total_instrumen_petugas,
            'instrumen_kelompok_20' => $result->total_instrumen_kelompok_20,
            'instrumen_kelompok_80' => $result->total_instrumen_kelompok_80,
            'anastesi' => $anastesiPool,
            'dokter_anastesi' => $result->total_dokter_anastesi,
            'perawat_anastesi_pool' => $perawatAnastesiPool,
            'perawat_anastesi' => $result->total_perawat_anastesi,
            'perawat_anastesi_premi_bersama' => $perawatAnastesiPremiBersama,
        ];
        $payload['total_dibagikan'] = $details->sum('total_received');
        $payload['total_tidak_dibagikan'] = max(
            0,
            $result->total_operasi - $payload['total_dibagikan'] - $result->total_premi_bersama
        );
        $payload['recipient_groups'] = $details
            ->groupBy('role')
            ->map(fn ($items) => [
                'role' => $items->first()['role'],
                'role_label' => $items->first()['role_label'],
                'pool_total' => $items->first()['pool_total'],
                'recipient_count' => $items->count(),
                'total_received' => $items->sum('total_received'),
                'items' => $items->values(),
            ])
            ->sortBy(fn ($group) => array_search($group['role'], array_keys(self::ROLE_SOURCES), true))
            ->values();

        return $payload;
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate operasi tidak ditemukan.');
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
                abort(404, 'Data generate operasi tidak ditemukan.');
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
                abort(404, 'Data generate operasi tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data operasi yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(string $jenisOperasi, int $totalOperasi, bool $strict = false): array
    {
        $config = $this->repository->getConfig($jenisOperasi);
        $configPayload = $this->configPayload($config);
        $percentages = $configPayload['percentages'];

        $instrumen = $this->portion($totalOperasi, $percentages['instrumen_percent']);
        $premiBersamaInstrumen = $this->portion($instrumen, $percentages['instrumen_premi_bersama_percent']);
        $instrumenPetugas = $this->portion($instrumen, $percentages['instrumen_petugas_percent']);
        $instrumen20 = $this->portion($instrumenPetugas, $percentages['instrumen_petugas_kelompok_20_percent']);
        $instrumen80 = $this->portion($instrumenPetugas, $percentages['instrumen_petugas_kelompok_80_percent']);
        $anastesiPool = $this->portion($totalOperasi, $percentages['dokter_anastesi_percent']);
        $perawatAnastesiPool = $this->portion($anastesiPool, $percentages['perawat_anastesi_percent']);
        $perawatAnastesi = $this->portion($perawatAnastesiPool, $percentages['perawat_anastesi_petugas_percent']);
        $perawatAnastesiPremiBersama = $this->portion($perawatAnastesiPool, $percentages['perawat_anastesi_premi_bersama_percent']);
        $premiBersama = $premiBersamaInstrumen + $perawatAnastesiPremiBersama;
        $dokterAnastesi = max(0, $anastesiPool - $perawatAnastesiPool);

        $pools = [
            'instrumen' => $instrumen,
            'premi_bersama' => $premiBersama,
            'premi_bersama_instrumen' => $premiBersamaInstrumen,
            'instrumen_petugas' => $instrumenPetugas,
            'instrumen_kelompok_20' => $instrumen20,
            'instrumen_kelompok_80' => $instrumen80,
            'anastesi' => $anastesiPool,
            'dokter_anastesi' => $dokterAnastesi,
            'perawat_anastesi_pool' => $perawatAnastesiPool,
            'perawat_anastesi' => $perawatAnastesi,
            'perawat_anastesi_premi_bersama' => $perawatAnastesiPremiBersama,
        ];

        $recipients = [];
        $recipients = array_merge($recipients, $this->splitPool('instrumen_20', $pools['instrumen_kelompok_20'], $configPayload, $strict));
        $recipients = array_merge($recipients, $this->splitPool('instrumen_80', $pools['instrumen_kelompok_80'], $configPayload, $strict));
        $recipients = array_merge($recipients, $this->splitPool('dokter_anastesi', $pools['dokter_anastesi'], $configPayload, $strict));
        $recipients = array_merge($recipients, $this->splitPool('perawat_anastesi', $pools['perawat_anastesi'], $configPayload, $strict));

        return [
            'jenis_operasi' => $jenisOperasi,
            'jenis_operasi_label' => $this->typeLabel($jenisOperasi),
            'total_operasi' => $totalOperasi,
            'config' => $configPayload,
            'pools' => $pools,
            'recipients' => $recipients,
            'total_dibagikan' => collect($recipients)->sum('total_received'),
        ];
    }

    private function splitPool(string $role, int $pool, array $config, bool $strict): array
    {
        $items = $config['recipients'][$role] ?? [];

        if ($strict && $pool > 0 && empty($items)) {
            throw ValidationException::withMessages([
                "recipients.{$role}" => self::ROLE_LABELS[$role].' wajib dipilih sebelum generate.',
            ]);
        }

        if (empty($items)) {
            return [];
        }

        $recipientCount = count($items);
        $base = intdiv($pool, $recipientCount);
        $remainder = $pool % $recipientCount;
        $allocationPercent = $pool > 0 ? round(100 / $recipientCount, 2) : null;

        return collect($items)->values()->map(function ($item, $index) use ($role, $pool, $base, $remainder, $allocationPercent) {
            return [
                'role' => $role,
                'role_label' => self::ROLE_LABELS[$role],
                'pegawai_source' => $item['pegawai_source'],
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'] ?? null,
                'allocation_percent' => $allocationPercent,
                'pool_total' => $pool,
                'total_received' => $base + ($index < $remainder ? 1 : 0),
            ];
        })->all();
    }

    private function hydrateRecipients(array $input): array
    {
        $result = [];

        foreach (self::ROLE_SOURCES as $role => $source) {
            $ids = collect($input[$role] ?? [])
                ->filter()
                ->map(fn ($id) => trim((string) $id))
                ->unique()
                ->values();

            $result[$role] = $ids->map(function ($id) use ($role, $source) {
                $row = $source === 'dokter'
                    ? $this->repository->findDokter($id)
                    : $this->repository->findPegawai($id);

                if (! $row) {
                    throw ValidationException::withMessages([
                        "recipients.{$role}" => self::ROLE_LABELS[$role].' tidak valid.',
                    ]);
                }

                return $source === 'dokter'
                    ? [
                        'pegawai_source' => 'dokter',
                        'pegawai_id' => $row->kd_dokter,
                        'pegawai_name' => $row->nm_dokter,
                        'pegawai_position' => 'Dokter',
                    ]
                    : [
                        'pegawai_source' => 'pegawai',
                        'pegawai_id' => $row->nik,
                        'pegawai_name' => $row->nama,
                        'pegawai_position' => $row->jbtn,
                    ];
            })->all();
        }

        return $result;
    }

    private function configPayload($config): array
    {
        $recipients = [];
        foreach (array_keys(self::ROLE_SOURCES) as $role) {
            $recipients[$role] = [];
        }

        foreach ($config->pegawai as $pegawai) {
            $recipients[$pegawai->role][] = [
                'pegawai_source' => $pegawai->pegawai_source,
                'pegawai_id' => $pegawai->pegawai_id,
                'pegawai_name' => $pegawai->pegawai_name,
                'pegawai_position' => $pegawai->pegawai_position,
                'text' => trim($pegawai->pegawai_id.' - '.$pegawai->pegawai_name),
            ];
        }

        return [
            'id' => $config->id,
            'jenis_operasi' => $config->jenis_operasi,
            'jenis_operasi_label' => $this->typeLabel($config->jenis_operasi),
            'percentages' => $this->percentagePayload($config->toArray()),
            'recipients' => $recipients,
            'role_labels' => self::ROLE_LABELS,
        ];
    }

    private function percentagePayload(array $data): array
    {
        return [
            'instrumen_percent' => (float) ($data['instrumen_percent'] ?? 10),
            'instrumen_premi_bersama_percent' => (float) ($data['instrumen_premi_bersama_percent'] ?? 20),
            'instrumen_petugas_percent' => (float) ($data['instrumen_petugas_percent'] ?? 80),
            'instrumen_petugas_kelompok_20_percent' => (float) ($data['instrumen_petugas_kelompok_20_percent'] ?? 20),
            'instrumen_petugas_kelompok_80_percent' => (float) ($data['instrumen_petugas_kelompok_80_percent'] ?? 80),
            'dokter_anastesi_percent' => (float) ($data['dokter_anastesi_percent'] ?? 40),
            'perawat_anastesi_percent' => (float) ($data['perawat_anastesi_percent'] ?? 10),
            'perawat_anastesi_petugas_percent' => (float) ($data['perawat_anastesi_petugas_percent'] ?? 80),
            'perawat_anastesi_premi_bersama_percent' => (float) ($data['perawat_anastesi_premi_bersama_percent'] ?? 20),
        ];
    }

    private function portion(int $amount, float $percent): int
    {
        return (int) round($amount * $percent / 100);
    }

    private function resultPayload($row): array
    {
        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'jenis_operasi' => $row->jenis_operasi,
            'jenis_operasi_label' => $this->typeLabel($row->jenis_operasi),
            'total_operasi' => $row->total_operasi,
            'total_instrumen' => $row->total_instrumen,
            'total_premi_bersama' => $row->total_premi_bersama,
            'total_instrumen_petugas' => $row->total_instrumen_petugas,
            'total_instrumen_kelompok_20' => $row->total_instrumen_kelompok_20,
            'total_instrumen_kelompok_80' => $row->total_instrumen_kelompok_80,
            'total_dokter_anastesi' => $row->total_dokter_anastesi,
            'total_perawat_anastesi' => $row->total_perawat_anastesi,
            'config_snapshot' => $row->config_snapshot,
            'details_count' => $row->details_count ?? $row->details?->count() ?? 0,
            'details' => $row->relationLoaded('details')
                ? $row->details->map(fn ($detail) => [
                    'role' => $detail->role,
                    'role_label' => $detail->role_label,
                    'pegawai_source' => $detail->pegawai_source,
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

    private function typeLabel(string $jenisOperasi): string
    {
        return $jenisOperasi === 'bpjs' ? 'BPJS' : 'Umum';
    }
}
