<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateGiziRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateGiziService
{
    private const ROLE_LABELS = [
        'konsul_pegawai' => 'Pegawai Konsul',
        'diit_petugas' => 'Petugas Diit',
    ];

    private const GROUP_LABELS = [
        'konsul' => 'Konsul',
        'diit' => 'Diit',
    ];

    public function __construct(
        protected generateGiziRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisGizi = null)
    {
        return $this->repository
            ->getResults($periode, $jenisGizi)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisGizi): array
    {
        return [
            'periode' => $periode,
            'jenis_gizi' => $jenisGizi,
            'jenis_gizi_label' => $this->typeLabel($jenisGizi),
            ...$this->repository->getSummary($periode, $jenisGizi),
        ];
    }

    public function getConfig(string $jenisGizi): array
    {
        return $this->configPayload($this->repository->getConfig($jenisGizi));
    }

    public function updateConfig(string $jenisGizi, array $data): array
    {
        $mappingGroups = [
            'konsul' => collect($data['konsul_jnsTindakan_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'diit' => collect($data['diit_jnsTindakan_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
        $recipients = $this->hydrateRecipients($data['recipients'] ?? []);

        return $this->configPayload(
            $this->repository->saveConfig(
                $jenisGizi,
                $this->configFormPayload($data),
                $mappingGroups,
                $recipients
            )
        );
    }

    public function mappingOptions(?string $keyword = null)
    {
        return $this->repository
            ->mappingOptions($keyword)
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'jenis' => $item->jenis,
                'jumlah_mapping' => (int) $item->jumlah_mapping,
                'text' => trim($item->kode.' - '.$item->jenis.' ('.$item->jumlah_mapping.' mapping)'),
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

    public function preview(string $periode, string $jenisGizi): array
    {
        return $this->calculate($periode, $jenisGizi);
    }

    public function generate(string $periode, string $jenisGizi): array
    {
        return DB::transaction(function () use ($periode, $jenisGizi) {
            $existing = $this->repository->findExistingForUpdate($periode, $jenisGizi);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data Gizi periode dan jenis ini sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($periode, $jenisGizi, true);

            return $this->resultPayload(
                $this->repository->saveResult($periode, $jenisGizi, $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate Gizi tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $payload['source'] = [
            'source_periode' => $result->source_periode,
            'source_period_mode' => $result->source_period_mode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($result->source_period_mode),
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d'),
            'jumlah_data_sumber' => $result->jumlah_data_sumber,
            'jumlah_pasien_sumber' => $result->jumlah_pasien_sumber,
            'jumlah_pasien' => $result->jumlah_pasien,
            'jumlah_tindakan' => $result->jumlah_tindakan,
            'jumlah_tindakan_konsul' => $result->jumlah_tindakan_konsul,
            'jumlah_tindakan_diit' => $result->jumlah_tindakan_diit,
            'grand_total' => $result->grand_total,
            'grand_total_konsul' => $result->grand_total_konsul,
            'grand_total_diit' => $result->grand_total_diit,
        ];
        $payload['pools'] = $this->poolPayload($result);
        $payload['kelompok_groups'] = collect($payload['details'])
            ->groupBy('kelompok')
            ->map(fn ($items) => [
                'kelompok' => $items->first()['kelompok'],
                'kelompok_label' => $items->first()['kelompok_label'],
                'jumlah_tindakan' => $items->count(),
                'grand_total' => $items->sum('biaya_rawat'),
            ])
            ->values();
        $payload['source_groups'] = collect($payload['details'])
            ->groupBy('source_table')
            ->map(fn ($items) => [
                'source_table' => $items->first()['source_table'],
                'source_label' => $items->first()['source_label'],
                'jumlah_tindakan' => $items->count(),
                'grand_total' => $items->sum('biaya_rawat'),
            ])
            ->values();
        $payload['recipient_groups'] = collect($payload['recipients'])
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
                abort(404, 'Data generate Gizi tidak ditemukan.');
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
                abort(404, 'Data generate Gizi tidak ditemukan.');
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
                abort(404, 'Data generate Gizi tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data Gizi yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(string $periode, string $jenisGizi, bool $strict = false): array
    {
        $config = $this->configPayload($this->repository->getConfig($jenisGizi));
        $hasMapping = ! empty($config['konsul_jnsTindakan_ids'])
            || ! empty($config['diit_jnsTindakan_ids']);

        if ($strict && ! $hasMapping) {
            throw ValidationException::withMessages([
                'jnsTindakan_ids' => 'Mapping Konsul atau Diit wajib dipilih sebelum generate.',
            ]);
        }

        $source = $this->repository->getSourceData($periode, $jenisGizi, $config);

        if ($strict && ($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'jnsTindakan_id' => 'Mapping Gizi belum memiliki rincian tindakan.',
            ]);
        }

        $grandKonsul = (int) $source['grand_total_konsul'];
        $grandDiit = (int) $source['grand_total_diit'];
        $konsulPegawai = $this->portion($grandKonsul, $config['konsul_pegawai_percent']);
        $konsulPremiBersama = $this->portion($grandKonsul, $config['konsul_premi_bersama_percent']);
        $diitPetugasPool = $this->portion($grandDiit, $config['diit_petugas_percent']);
        $diitDivider = max(1, (int) $config['diit_petugas_divider']);
        $diitPetugasPerOrang = (int) round($diitPetugasPool / $diitDivider);
        $diitPremiBersama = $config['diit_premi_bersama_enabled']
            ? $this->portion($grandDiit, $config['diit_premi_bersama_percent'])
            : 0;

        $recipients = array_merge(
            $this->splitPool(
                'konsul_pegawai',
                $konsulPegawai,
                $config['recipients']['konsul_pegawai'] ?? []
            ),
            $this->fixedRecipients(
                'diit_petugas',
                $diitPetugasPool,
                $diitDivider,
                round((float) $config['diit_petugas_percent'] / $diitDivider, 2),
                $config['recipients']['diit_petugas'] ?? []
            )
        );

        $pools = [
            'konsul_pegawai' => $konsulPegawai,
            'konsul_premi_bersama' => $konsulPremiBersama,
            'diit_petugas_pool' => $diitPetugasPool,
            'diit_petugas_divider' => $diitDivider,
            'diit_petugas_per_orang' => $diitPetugasPerOrang,
            'diit_premi_bersama' => $diitPremiBersama,
            'premi_bersama' => $konsulPremiBersama + $diitPremiBersama,
            'total_dibagikan' => (int) collect($recipients)->sum('total_received'),
        ];
        $warnings = $this->warnings($source, $config, $pools);

        if ($strict && ! empty($warnings['blocking'])) {
            throw ValidationException::withMessages([
                'config' => implode(' ', $warnings['blocking']),
            ]);
        }

        $configSnapshot = [
            ...$config,
            'source_periode' => $source['source_periode'],
            'source_period_mode' => $source['source_period_mode'],
            'source_tgl_awal' => $source['source_tgl_awal'],
            'source_tgl_akhir' => $source['source_tgl_akhir'],
            'mapping' => $source['mapping'],
            'pools' => $pools,
            'recipient_counts' => [
                'konsul_pegawai' => count($config['recipients']['konsul_pegawai'] ?? []),
                'diit_petugas' => count($config['recipients']['diit_petugas'] ?? []),
            ],
        ];

        return [
            'periode' => $periode,
            'jenis_gizi' => $jenisGizi,
            'jenis_gizi_label' => $this->typeLabel($jenisGizi),
            'source_periode' => $source['source_periode'],
            'source_tgl_awal' => $source['source_tgl_awal'],
            'source_tgl_akhir' => $source['source_tgl_akhir'],
            'source' => $source,
            'pools' => $pools,
            'recipients' => $recipients,
            'recipient_groups' => collect($recipients)
                ->groupBy('role')
                ->map(fn ($items) => [
                    'role' => $items->first()['role'],
                    'role_label' => $items->first()['role_label'],
                    'pool_total' => $items->first()['pool_total'],
                    'recipient_count' => $items->count(),
                    'total_received' => $items->sum('total_received'),
                    'items' => $items->values(),
                ])
                ->values(),
            'config' => $configSnapshot,
            'config_snapshot' => $configSnapshot,
            'warnings' => $warnings,
            'can_generate' => empty($warnings['blocking']),
        ];
    }

    private function warnings(array $source, array $config, array $pools): array
    {
        $blocking = [];
        $info = [];
        $hasMapping = ! empty($config['konsul_jnsTindakan_ids'])
            || ! empty($config['diit_jnsTindakan_ids']);

        if (! $hasMapping) {
            $blocking[] = 'Mapping Konsul atau Diit belum dipilih.';
        } elseif (($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            $blocking[] = 'Mapping Gizi belum memiliki rincian tindakan.';
        }

        $konsulRecipientCount = count($config['recipients']['konsul_pegawai'] ?? []);
        $diitRecipientCount = count($config['recipients']['diit_petugas'] ?? []);
        $diitDivider = max(1, (int) ($config['diit_petugas_divider'] ?? 1));

        if (($pools['konsul_pegawai'] ?? 0) > 0 && $konsulRecipientCount < 1) {
            $blocking[] = 'Pegawai penerima Konsul wajib dipilih.';
        }

        if (($pools['diit_petugas_pool'] ?? 0) > 0 && $diitRecipientCount < 1) {
            $blocking[] = 'Petugas penerima Diit wajib dipilih.';
        }

        if (($pools['diit_petugas_pool'] ?? 0) > 0 && $diitRecipientCount > $diitDivider) {
            $blocking[] = 'Jumlah petugas Diit tidak boleh melebihi pembagi formula.';
        }

        if (($pools['diit_petugas_pool'] ?? 0) > 0 && $diitRecipientCount > 0 && $diitRecipientCount < $diitDivider) {
            $info[] = 'Jumlah petugas Diit lebih sedikit dari pembagi, sehingga sebagian pool Diit tidak dibagikan.';
        }

        if ($source['jumlah_data_sumber'] < 1) {
            $info[] = 'Tidak ada tindakan sumber pada periode dan penjamin ini.';
        } elseif ($source['jumlah_tindakan'] < 1) {
            $info[] = 'Ada tindakan sumber, tetapi belum ada yang cocok dengan mapping Konsul/Diit.';
        }

        return [
            'blocking' => $blocking,
            'info' => $info,
        ];
    }

    private function configFormPayload(array $data): array
    {
        return [
            'source_period_mode' => in_array(($data['source_period_mode'] ?? 'current'), ['current', 'previous'], true)
                ? $data['source_period_mode']
                : 'current',
            'konsul_pegawai_percent' => (float) ($data['konsul_pegawai_percent'] ?? 50),
            'konsul_premi_bersama_percent' => (float) ($data['konsul_premi_bersama_percent'] ?? 30),
            'diit_petugas_percent' => (float) ($data['diit_petugas_percent'] ?? 3),
            'diit_petugas_divider' => max(1, (int) ($data['diit_petugas_divider'] ?? 5)),
            'diit_premi_bersama_percent' => (float) ($data['diit_premi_bersama_percent'] ?? 37),
            'diit_premi_bersama_enabled' => (bool) ($data['diit_premi_bersama_enabled'] ?? false),
        ];
    }

    private function configPayload($config): array
    {
        $mappingGroups = [
            'konsul' => collect(),
            'diit' => collect(),
        ];

        if ($config->relationLoaded('mappings')) {
            foreach ($config->mappings as $mapping) {
                if (! isset($mappingGroups[$mapping->kelompok]) || ! $mapping->jenisTindakan) {
                    continue;
                }

                $mappingGroups[$mapping->kelompok]->push([
                    'id' => (int) $mapping->jenisTindakan->id,
                    'kode' => $mapping->jenisTindakan->kode,
                    'jenis' => $mapping->jenisTindakan->jenis,
                    'label' => trim($mapping->jenisTindakan->kode.' - '.$mapping->jenisTindakan->jenis),
                    'text' => trim($mapping->jenisTindakan->kode.' - '.$mapping->jenisTindakan->jenis),
                ]);
            }
        }

        if ($mappingGroups['konsul']->isEmpty() && $mappingGroups['diit']->isEmpty() && $config->jenisTindakan) {
            $mappingGroups['konsul']->push([
                'id' => (int) $config->jenisTindakan->id,
                'kode' => $config->jenisTindakan->kode,
                'jenis' => $config->jenisTindakan->jenis,
                'label' => trim($config->jenisTindakan->kode.' - '.$config->jenisTindakan->jenis),
                'text' => trim($config->jenisTindakan->kode.' - '.$config->jenisTindakan->jenis),
            ]);
        }

        $recipients = [
            'konsul_pegawai' => [],
            'diit_petugas' => [],
        ];

        if ($config->relationLoaded('pegawai')) {
            foreach ($config->pegawai as $pegawai) {
                if (! isset($recipients[$pegawai->role])) {
                    continue;
                }

                $recipients[$pegawai->role][] = [
                    'pegawai_id' => $pegawai->pegawai_id,
                    'pegawai_name' => $pegawai->pegawai_name,
                    'pegawai_position' => $pegawai->pegawai_position,
                    'text' => trim($pegawai->pegawai_id.' - '.$pegawai->pegawai_name),
                ];
            }
        }

        $konsulLabel = $this->mappingGroupLabel($mappingGroups['konsul']);
        $diitLabel = $this->mappingGroupLabel($mappingGroups['diit']);
        $allMappings = $mappingGroups['konsul']->merge($mappingGroups['diit']);

        return [
            'id' => $config->id,
            'jenis_gizi' => $config->jenis_gizi,
            'jenis_gizi_label' => $this->typeLabel($config->jenis_gizi),
            'source_period_mode' => $config->source_period_mode ?: 'current',
            'source_period_mode_label' => $this->sourcePeriodModeLabel($config->source_period_mode ?: 'current'),
            'jnsTindakan_id' => $config->jnsTindakan_id,
            'konsul_jnsTindakan_ids' => $mappingGroups['konsul']->pluck('id')->all(),
            'diit_jnsTindakan_ids' => $mappingGroups['diit']->pluck('id')->all(),
            'konsul_mapping_items' => $mappingGroups['konsul']->values()->all(),
            'diit_mapping_items' => $mappingGroups['diit']->values()->all(),
            'mapping_label' => $this->mappingGroupLabel($allMappings),
            'konsul_mapping_label' => $konsulLabel,
            'diit_mapping_label' => $diitLabel,
            'konsul_pegawai_percent' => (float) $config->konsul_pegawai_percent,
            'konsul_premi_bersama_percent' => (float) $config->konsul_premi_bersama_percent,
            'diit_petugas_percent' => (float) $config->diit_petugas_percent,
            'diit_petugas_divider' => max(1, (int) ($config->diit_petugas_divider ?: 1)),
            'diit_premi_bersama_percent' => (float) $config->diit_premi_bersama_percent,
            'diit_premi_bersama_enabled' => (bool) $config->diit_premi_bersama_enabled,
            'recipients' => $recipients,
            'role_labels' => self::ROLE_LABELS,
            'group_labels' => self::GROUP_LABELS,
        ];
    }

    private function resultPayload($row): array
    {
        $configSnapshot = $row->config_snapshot ?: [];

        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'source_periode' => $row->source_periode,
            'source_period_mode' => $row->source_period_mode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($row->source_period_mode),
            'source_tgl_awal' => optional($row->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($row->source_tgl_akhir)->format('Y-m-d'),
            'jenis_gizi' => $row->jenis_gizi,
            'jenis_gizi_label' => $this->typeLabel($row->jenis_gizi),
            'jnsTindakan_id' => $row->jnsTindakan_id,
            'kode_jenis_tindakan' => $row->kode_jenis_tindakan,
            'nama_jenis_tindakan' => $row->nama_jenis_tindakan,
            'mapping_label' => data_get($configSnapshot, 'mapping.label')
                ?? ($row->nama_jenis_tindakan
                    ? trim(($row->kode_jenis_tindakan ? $row->kode_jenis_tindakan.' - ' : '').$row->nama_jenis_tindakan)
                    : '-'),
            'mapping_groups' => data_get($configSnapshot, 'mapping.groups', []),
            'jumlah_data_sumber' => $row->jumlah_data_sumber,
            'jumlah_pasien_sumber' => $row->jumlah_pasien_sumber,
            'jumlah_pasien' => $row->jumlah_pasien,
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'jumlah_tindakan_konsul' => $row->jumlah_tindakan_konsul,
            'jumlah_tindakan_diit' => $row->jumlah_tindakan_diit,
            'grand_total' => $row->grand_total,
            'grand_total_konsul' => $row->grand_total_konsul,
            'grand_total_diit' => $row->grand_total_diit,
            'total_konsul_pegawai' => $row->total_konsul_pegawai,
            'total_konsul_premi_bersama' => $row->total_konsul_premi_bersama,
            'total_diit_petugas_pool' => $row->total_diit_petugas_pool,
            'diit_petugas_per_orang' => $row->diit_petugas_per_orang,
            'total_diit_premi_bersama' => $row->total_diit_premi_bersama,
            'total_premi_bersama' => $row->total_premi_bersama,
            'total_dibagikan' => $row->total_dibagikan,
            'details_count' => $row->details_count ?? $row->details?->count() ?? 0,
            'recipients_count' => $row->recipients_count ?? $row->recipients?->count() ?? 0,
            'config_snapshot' => $row->config_snapshot,
            'details' => $row->relationLoaded('details')
                ? $row->details->map(fn ($detail) => $this->detailPayload($detail))->values()
                : [],
            'recipients' => $row->relationLoaded('recipients')
                ? $row->recipients->map(fn ($recipient) => $this->recipientPayload($recipient))->values()
                : [],
            'is_locked' => $row->is_locked,
            'locked_at' => optional($row->locked_at)->format('d-m-Y H:i'),
            'locked_by_name' => $row->lockedBy?->name,
            'generate_by_name' => $row->generateBy?->name,
            'generated_at' => optional($row->updated_at)->format('d-m-Y H:i'),
        ];
    }

    private function detailPayload($detail): array
    {
        return [
            'id' => $detail->id,
            'mapping_tindakan_id' => $detail->mapping_tindakan_id,
            'jnsTindakan_id' => $detail->jnsTindakan_id,
            'kelompok' => $detail->kelompok,
            'kelompok_label' => $this->groupLabel($detail->kelompok),
            'source_table' => $detail->source_table,
            'sumber_tindakan' => $detail->sumber_tindakan,
            'source_label' => $this->sourceLabel($detail->source_table),
            'no_rawat' => $detail->no_rawat,
            'no_rkm_medis' => $detail->no_rkm_medis,
            'nm_pasien' => $detail->nm_pasien,
            'kd_pj' => $detail->kd_pj,
            'nama_penjamin' => $detail->nama_penjamin,
            'tanggal' => optional($detail->tanggal)->format('Y-m-d'),
            'jam' => $detail->jam,
            'kd_tindakan' => $detail->kd_tindakan,
            'nm_tindakan' => $detail->nm_tindakan,
            'kd_dokter' => $detail->kd_dokter,
            'nm_dokter' => $detail->nm_dokter,
            'nip' => $detail->nip,
            'nama_petugas' => $detail->nama_petugas,
            'biaya_rawat' => $detail->biaya_rawat,
        ];
    }

    private function recipientPayload($recipient): array
    {
        return [
            'role' => $recipient->role,
            'role_label' => $recipient->role_label,
            'pegawai_id' => $recipient->pegawai_id,
            'pegawai_name' => $recipient->pegawai_name,
            'pegawai_position' => $recipient->pegawai_position,
            'allocation_percent' => $recipient->allocation_percent,
            'pool_total' => $recipient->pool_total,
            'total_received' => $recipient->total_received,
        ];
    }

    private function splitPool(string $role, int $pool, array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $recipientCount = count($items);
        $base = intdiv($pool, $recipientCount);
        $remainder = $pool % $recipientCount;
        $allocationPercent = $pool > 0 ? round(100 / $recipientCount, 2) : null;

        return collect($items)
            ->values()
            ->map(fn ($item, $index) => [
                'role' => $role,
                'role_label' => self::ROLE_LABELS[$role],
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'] ?? null,
                'allocation_percent' => $allocationPercent,
                'pool_total' => $pool,
                'total_received' => $base + ($index < $remainder ? 1 : 0),
            ])
            ->all();
    }

    private function fixedRecipients(
        string $role,
        int $pool,
        int $divider,
        float $allocationPercent,
        array $items
    ): array {
        if (empty($items)) {
            return [];
        }

        $divider = max(1, $divider);
        $base = intdiv($pool, $divider);
        $remainder = $pool % $divider;

        return collect($items)
            ->values()
            ->map(fn ($item, $index) => [
                'role' => $role,
                'role_label' => self::ROLE_LABELS[$role],
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'] ?? null,
                'allocation_percent' => $allocationPercent,
                'pool_total' => $pool,
                'total_received' => $base + ($index < $remainder ? 1 : 0),
            ])
            ->all();
    }

    private function hydrateRecipients(array $input): array
    {
        $result = [
            'konsul_pegawai' => [],
            'diit_petugas' => [],
        ];

        foreach (array_keys(self::ROLE_LABELS) as $role) {
            $ids = collect($input[$role] ?? [])
                ->filter()
                ->map(fn ($id) => trim((string) $id))
                ->unique()
                ->values();

            $result[$role] = $ids
                ->map(function ($id) use ($role) {
                    $row = $this->repository->findPegawai($id);

                    if (! $row) {
                        throw ValidationException::withMessages([
                            "recipients.{$role}" => self::ROLE_LABELS[$role].' tidak valid.',
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

        return $result;
    }

    private function poolPayload($row): array
    {
        $configSnapshot = $row->config_snapshot ?: [];

        return [
            'konsul_pegawai' => $row->total_konsul_pegawai,
            'konsul_premi_bersama' => $row->total_konsul_premi_bersama,
            'diit_petugas_pool' => $row->total_diit_petugas_pool,
            'diit_petugas_divider' => (int) (data_get($configSnapshot, 'pools.diit_petugas_divider') ?? data_get($configSnapshot, 'diit_petugas_divider') ?? 1),
            'diit_petugas_per_orang' => $row->diit_petugas_per_orang,
            'diit_premi_bersama' => $row->total_diit_premi_bersama,
            'premi_bersama' => $row->total_premi_bersama,
            'total_dibagikan' => $row->total_dibagikan,
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

    private function portion(int $amount, float $percent): int
    {
        return (int) round($amount * $percent / 100);
    }

    private function mappingGroupLabel($items): ?string
    {
        $items = collect($items)->values();
        $first = $items->first();

        if (! $first) {
            return null;
        }

        return $items->count() > 1
            ? $first['label'].' + '.($items->count() - 1).' mapping'
            : $first['label'];
    }

    private function sourcePeriodModeLabel(?string $mode): string
    {
        return $mode === 'previous' ? 'Bulan Sebelumnya' : 'Periode Generate';
    }

    private function sourceLabel(string $sourceTable): string
    {
        return match ($sourceTable) {
            'rawat_jl_pr' => 'Rawat Jalan Paramedis',
            'rawat_inap_pr' => 'Rawat Inap Paramedis',
            'rawat_jl_dr' => 'Rawat Jalan Dokter',
            'rawat_inap_dr' => 'Rawat Inap Dokter',
            'rawat_jl_drpr' => 'Rawat Jalan Dokter & Paramedis',
            'rawat_inap_drpr' => 'Rawat Inap Dokter & Paramedis',
            default => $sourceTable,
        };
    }

    private function groupLabel(string $kelompok): string
    {
        return self::GROUP_LABELS[$kelompok] ?? $kelompok;
    }

    private function typeLabel(string $jenisGizi): string
    {
        return $jenisGizi === 'bpjs' ? 'BPJS Kesehatan' : 'Umum';
    }
}
