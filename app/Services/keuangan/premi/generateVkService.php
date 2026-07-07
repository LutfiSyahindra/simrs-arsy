<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateVkRepository;
use App\Services\mappingData\tindakanMappingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateVkService
{
    private const BPJS_ROLE = 'petugas_vk';
    private const BPJS_ROLE_LABEL = 'Petugas VK';

    public function __construct(
        protected generateVkRepository $repository,
        protected tindakanMappingService $tindakanMappingService
    ) {}

    public function getResults(?string $periode = null, ?string $jenisVk = null)
    {
        return $this->repository
            ->getResults($periode, $jenisVk)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisVk): array
    {
        return [
            'periode' => $periode,
            'jenis_vk' => $jenisVk,
            'jenis_vk_label' => $this->typeLabel($jenisVk),
            ...$this->repository->getSummary($periode, $jenisVk),
        ];
    }

    public function getConfig(string $jenisVk): array
    {
        return $this->configPayload($this->repository->getConfig($jenisVk));
    }

    public function updateConfig(string $jenisVk, array $data): array
    {
        $recipients = $this->hydrateRecipients($data['recipients'] ?? []);

        return $this->configPayload(
            $this->repository->saveConfig($jenisVk, [
                'bpjs_percent' => (float) ($data['bpjs_percent'] ?? 4),
                'bpjs_pembagi' => max(1, (int) ($data['bpjs_pembagi'] ?? 4)),
                'premi_bersama_percent' => (float) ($data['premi_bersama_percent'] ?? 20),
                'distribution_mode' => $this->distributionMode($data['distribution_mode'] ?? 'rata'),
            ], $recipients)
        );
    }

    public function getTindakanOptions(?string $keyword = null)
    {
        return $this->tindakanMappingService
            ->searchTindakan((string) $keyword)
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

    public function copyPreview(string $periode, string $jenisVk): array
    {
        $targetPeriode = Carbon::createFromFormat('Y-m-d', $periode.'-01')
            ->addMonthNoOverflow()
            ->format('Y-m');
        $items = $this->repository
            ->getResults($periode, $jenisVk)
            ->map(fn ($row) => $this->resultPayload($row))
            ->values();

        return [
            'source_periode' => $periode,
            'target_periode' => $targetPeriode,
            'jenis_vk' => $jenisVk,
            'jenis_vk_label' => $this->typeLabel($jenisVk),
            'count' => $items->count(),
            'jumlah_tindakan' => $items->sum('jumlah_tindakan'),
            'total_vk' => $items->sum('total_vk'),
            'items' => $items,
        ];
    }

    public function generate(
        string $periode,
        string $jenisVk,
        string $namaTindakan,
        int $plotingId,
        int $jumlahTindakan,
        int $nominal
    ): array {
        return DB::transaction(
            fn () => $this->generateRow(
                $periode,
                $jenisVk,
                $namaTindakan,
                $plotingId,
                $jumlahTindakan,
                $nominal
            )
        );
    }

    public function generateMany(
        string $periode,
        string $jenisVk,
        array $entries
    ): array {
        return DB::transaction(function () use ($periode, $jenisVk, $entries) {
            $results = [];
            $seen = [];

            foreach ($entries as $index => $entry) {
                $namaTindakan = $this->normalizeManualName((string) $entry['nm_tindakan']);
                $plotingId = (int) $entry['plotingPremi_id'];
                $key = mb_strtolower($namaTindakan).'|'.$plotingId;

                if (isset($seen[$key])) {
                    throw ValidationException::withMessages([
                        "entries.{$index}.nm_tindakan" => 'Tindakan dan ploting tidak boleh duplikat dalam satu generate.',
                    ]);
                }

                $seen[$key] = true;
                $results[] = $this->generateRow(
                    $periode,
                    $jenisVk,
                    $namaTindakan,
                    $plotingId,
                    (int) $entry['jumlah_tindakan'],
                    (int) $entry['nominal_hitung'],
                    "entries.{$index}."
                );
            }

            return [
                'periode' => $periode,
                'jenis_vk' => $jenisVk,
                'jenis_vk_label' => $this->typeLabel($jenisVk),
                'count' => count($results),
                'jumlah_tindakan' => collect($results)->sum('jumlah_tindakan'),
                'total_vk' => collect($results)->sum('total_vk'),
                'items' => $results,
            ];
        });
    }

    private function generateRow(
        string $periode,
        string $jenisVk,
        string $namaTindakan,
        int $plotingId,
        int $jumlahTindakan,
        int $nominal,
        string $errorPrefix = ''
    ): array {
        if ($this->normalizeManualName($namaTindakan) === '') {
            throw ValidationException::withMessages([
                $errorPrefix.'nm_tindakan' => 'Tindakan wajib diisi.',
            ]);
        }

        $tindakan = $this->manualTindakanPayload($namaTindakan);

        $ploting = $this->repository->findPloting($plotingId);

        if (! $ploting) {
            throw ValidationException::withMessages([
                $errorPrefix.'plotingPremi_id' => 'Ploting premi tidak ditemukan.',
            ]);
        }

        $existing = $this->repository->findExistingForUpdate(
            $periode,
            $jenisVk,
            $tindakan['sumber_tindakan'],
            $tindakan['kd_tindakan'],
            $plotingId
        );

        if ($existing?->is_locked) {
            throw ValidationException::withMessages([
                $errorPrefix.'nm_tindakan' => 'Data VK tindakan dan ploting ini sudah dikunci.',
            ]);
        }

        $calculation = $this->calculate($jenisVk, $jumlahTindakan, $nominal, true, $errorPrefix);

        return $this->resultPayload(
            $this->repository->saveResult(
                $periode,
                $jenisVk,
                $tindakan,
                $ploting,
                $jumlahTindakan,
                $nominal,
                $calculation
            )->fresh(['details', 'lockedBy:id,name', 'generateBy:id,name'])
        );
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate VK tidak ditemukan.');
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

    public function lockAll(string $periode, string $jenisVk, User $user): array
    {
        return DB::transaction(function () use ($periode, $jenisVk, $user) {
            $results = $this->repository->getUnlockedForPeriodAndType($periode, $jenisVk);

            if ($results->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Tidak ada data VK terbuka yang bisa dikunci.',
                ]);
            }

            $lockedCount = $this->repository->updateManyLock($results, $user->id);

            return [
                'periode' => $periode,
                'jenis_vk' => $jenisVk,
                'jenis_vk_label' => $this->typeLabel($jenisVk),
                'locked_count' => $lockedCount,
            ];
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
                abort(404, 'Data generate VK tidak ditemukan.');
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
                abort(404, 'Data generate VK tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data VK yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function resultPayload($row): array
    {
        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'jenis_vk' => $row->jenis_vk,
            'jenis_vk_label' => $this->typeLabel($row->jenis_vk),
            'source_key' => $row->source_key,
            'sumber_tindakan' => $row->sumber_tindakan,
            'sumber_label' => $this->sourceLabel($row->sumber_tindakan),
            'kd_tindakan' => $row->kd_tindakan,
            'nm_tindakan' => $row->nm_tindakan,
            'kd_pj' => $row->kd_pj,
            'nm_pj' => $row->nm_pj,
            'pj_label' => $row->nm_pj ?: ($row->kd_pj ?: '-'),
            'parent_kd_tindakan' => $row->parent_kd_tindakan,
            'parent_nm_tindakan' => $row->parent_nm_tindakan,
            'parent_label' => $this->parentLabel($row->parent_kd_tindakan, $row->parent_nm_tindakan),
            'display_text' => $row->sumber_tindakan === 'MANUAL'
                ? $row->nm_tindakan
                : trim($row->kd_tindakan.' - '.$row->nm_tindakan),
            'plotingPremi_id' => $row->plotingPremi_id,
            'kode_ploting' => $row->kode_ploting,
            'nama_ploting' => $row->nama_ploting,
            'ploting_label' => trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-')),
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'nominal_hitung' => $row->nominal_hitung,
            'total_vk' => $row->total_vk,
            'total_vk_awal' => $row->total_vk_awal ?: $row->total_vk,
            'bpjs_pool' => $row->bpjs_pool ?? 0,
            'total_dibagikan' => $row->total_dibagikan ?? 0,
            'total_premi_bersama' => $row->total_premi_bersama ?? 0,
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

    private function calculate(
        string $jenisVk,
        int $jumlahTindakan,
        int $nominal,
        bool $strict,
        string $errorPrefix = ''
    ): array {
        $baseTotal = $jumlahTindakan * $nominal;
        $config = $this->configPayload($this->repository->getConfig($jenisVk));
        $bpjsPool = 0;
        $premiBersama = 0;
        $totalVk = $baseTotal;
        $recipients = [];

        if ($jenisVk === 'bpjs') {
            $bpjsPool = (int) round($baseTotal * $config['bpjs_percent'] / 100);
            $hasilPerhitungan = (int) round($bpjsPool / max(1, $config['bpjs_pembagi']));
            $premiBersama = (int) round($baseTotal * $config['premi_bersama_percent'] / 100);
            $recipients = $this->splitBpjsPool(
                $hasilPerhitungan,
                $config['recipients'],
                $config['distribution_mode'],
                $strict,
                $errorPrefix
            );
            $totalVk = $baseTotal;
        }

        return [
            'total_vk_awal' => $baseTotal,
            'bpjs_pool' => $bpjsPool,
            'total_vk' => $totalVk,
            'recipients' => $recipients,
            'total_dibagikan' => collect($recipients)->sum('total_received'),
            'total_premi_bersama' => $premiBersama,
            'config_snapshot' => [
                ...$config,
                'bpjs_hasil_perhitungan' => $jenisVk === 'bpjs' ? ($hasilPerhitungan ?? 0) : $baseTotal,
                'total_premi_bersama' => $premiBersama,
                'formula_label' => $jenisVk === 'bpjs'
                    ? 'Total VK awal; pegawai dari persen BPJS / pembagi; premi bersama dari persen premi bersama'
                    : 'Jumlah tindakan x nominal hitung',
            ],
        ];
    }

    private function splitBpjsPool(
        int $pool,
        array $recipients,
        string $distributionMode,
        bool $strict,
        string $errorPrefix
    ): array {
        if ($strict && $pool > 0 && empty($recipients)) {
            throw ValidationException::withMessages([
                $errorPrefix.'recipients' => 'Pegawai penerima VK BPJS wajib dipilih pada konfigurasi sebelum generate.',
            ]);
        }

        if (empty($recipients)) {
            return [];
        }

        if ($distributionMode === 'per_pegawai') {
            return collect($recipients)->values()->map(function ($item) use ($pool) {
                return [
                    'role' => self::BPJS_ROLE,
                    'role_label' => self::BPJS_ROLE_LABEL,
                    'pegawai_id' => $item['pegawai_id'],
                    'pegawai_name' => $item['pegawai_name'],
                    'pegawai_position' => $item['pegawai_position'] ?? null,
                    'allocation_percent' => $pool > 0 ? 100 : null,
                    'pool_total' => $pool,
                    'total_received' => $pool,
                ];
            })->all();
        }

        $recipientCount = count($recipients);
        $base = intdiv($pool, $recipientCount);
        $remainder = $pool % $recipientCount;
        $allocationPercent = $pool > 0 ? round(100 / $recipientCount, 2) : null;

        return collect($recipients)->values()->map(function ($item, $index) use ($pool, $base, $remainder, $allocationPercent) {
            return [
                'role' => self::BPJS_ROLE,
                'role_label' => self::BPJS_ROLE_LABEL,
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
        return collect($input)
            ->filter()
            ->map(fn ($id) => trim((string) $id))
            ->unique()
            ->values()
            ->map(function ($id) {
                $row = $this->repository->findPegawai($id);

                if (! $row) {
                    throw ValidationException::withMessages([
                        'recipients' => 'Pegawai penerima VK BPJS tidak valid.',
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

    private function configPayload($config): array
    {
        return [
            'id' => $config->id,
            'jenis_vk' => $config->jenis_vk,
            'jenis_vk_label' => $this->typeLabel($config->jenis_vk),
            'bpjs_percent' => (float) ($config->bpjs_percent ?? 4),
            'bpjs_pembagi' => max(1, (int) ($config->bpjs_pembagi ?? 4)),
            'premi_bersama_percent' => (float) ($config->premi_bersama_percent ?? 20),
            'distribution_mode' => $this->distributionMode($config->distribution_mode ?? 'rata'),
            'distribution_mode_label' => $this->distributionModeLabel($config->distribution_mode ?? 'rata'),
            'recipients' => $config->pegawai->map(fn ($pegawai) => [
                'pegawai_id' => $pegawai->pegawai_id,
                'pegawai_name' => $pegawai->pegawai_name,
                'pegawai_position' => $pegawai->pegawai_position,
                'text' => trim($pegawai->pegawai_id.' - '.$pegawai->pegawai_name),
            ])->values()->all(),
        ];
    }

    private function distributionMode(string $mode): string
    {
        return in_array($mode, ['rata', 'per_pegawai'], true) ? $mode : 'rata';
    }

    private function distributionModeLabel(string $mode): string
    {
        return $this->distributionMode($mode) === 'per_pegawai'
            ? 'Setiap pegawai mendapat hasil perhitungan'
            : 'Bagi rata ke semua pegawai';
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

    private function typeLabel(string $jenisVk): string
    {
        return $jenisVk === 'bpjs' ? 'BPJS' : 'Umum';
    }

    private function manualTindakanPayload(string $namaTindakan): array
    {
        $namaTindakan = $this->normalizeManualName($namaTindakan);
        $hash = substr(hash('sha1', mb_strtolower($namaTindakan)), 0, 16);

        return [
            'source_key' => 'manual:'.$hash,
            'sumber_tindakan' => 'MANUAL',
            'kd_tindakan' => 'MAN-'.$hash,
            'nm_tindakan' => $namaTindakan,
            'kd_pj' => null,
            'nm_pj' => null,
            'parent_kd_tindakan' => null,
            'parent_nm_tindakan' => null,
        ];
    }

    private function normalizeManualName(string $namaTindakan): string
    {
        return preg_replace('/\s+/', ' ', trim($namaTindakan)) ?: '';
    }

    private function sourceLabel(string $source): string
    {
        return $source === 'MANUAL'
            ? 'Manual'
            : $this->tindakanMappingService->sourceLabel($source);
    }

    private function parentLabel($parentKode, $parentNama): string
    {
        if (! $parentKode && ! $parentNama) {
            return '-';
        }

        if ($parentKode && $parentNama) {
            return $parentKode.' - '.$parentNama;
        }

        return $parentNama ?: $parentKode;
    }
}
