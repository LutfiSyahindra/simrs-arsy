<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateNicuRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateNicuService
{
    private const ROLE_LABELS = [
        'perawat_nicu' => 'Perawat NICU',
        'pegawai_nicu_khusus' => 'Pegawai NICU Khusus',
    ];

    public function __construct(
        protected generateNicuRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisIcu = null)
    {
        return $this->repository
            ->getResults($periode, $jenisIcu)
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisIcu): array
    {
        return [
            'periode' => $periode,
            'jenis_nicu' => $jenisIcu,
            'jenis_nicu_label' => $this->typeLabel($jenisIcu),
            ...$this->repository->getSummary($periode, $jenisIcu),
        ];
    }

    public function getConfig(string $jenisIcu): array
    {
        return $this->configPayload($this->repository->getConfig($jenisIcu));
    }

    public function updateConfig(string $jenisIcu, array $data): array
    {
        $mappingIds = collect($data['jnsTindakan_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $recipients = $this->hydrateRecipients($data['recipients'] ?? []);

        return $this->configPayload(
            $this->repository->saveConfig(
                $jenisIcu,
                $this->configFormPayload($data),
                $mappingIds,
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

    public function criticalActionOptions(?string $keyword = null)
    {
        return $this->repository
            ->criticalActionOptions($keyword)
            ->map(fn ($item) => [
                'id' => $item->nm_perawatan,
                'text' => $item->nm_perawatan,
                'nm_perawatan' => $item->nm_perawatan,
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

    public function preview(string $periode, string $jenisIcu): array
    {
        return $this->calculate($periode, $jenisIcu);
    }

    public function generate(string $periode, string $jenisIcu): array
    {
        return DB::transaction(function () use ($periode, $jenisIcu) {
            $existing = $this->repository->findExistingForUpdate($periode, $jenisIcu);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data NICU periode dan jenis ini sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($periode, $jenisIcu, true);

            return $this->resultPayload(
                $this->repository->saveResult($periode, $jenisIcu, $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate NICU tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $payload['source'] = [
            'source_periode' => $result->source_periode,
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d'),
            'jumlah_pasien_sumber' => $result->jumlah_pasien_sumber,
            'jumlah_pasien_nicu' => $result->jumlah_pasien_nicu,
            'jumlah_pasien' => $result->jumlah_pasien,
            'jumlah_tindakan' => $result->jumlah_tindakan,
            'jumlah_tindakan_nicu' => $result->jumlah_tindakan_nicu,
            'jumlah_tindakan_kritikal' => $result->jumlah_tindakan_kritikal,
            'grand_total' => $result->grand_total,
        ];
        $payload['pools'] = $this->poolPayload($result);
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
                abort(404, 'Data generate NICU tidak ditemukan.');
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
                abort(404, 'Data generate NICU tidak ditemukan.');
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
                abort(404, 'Data generate NICU tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data NICU yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(string $periode, string $jenisIcu, bool $strict = false): array
    {
        $config = $this->configPayload($this->repository->getConfig($jenisIcu));

        if ($strict && empty($config['jnsTindakan_ids'])) {
            throw ValidationException::withMessages([
                'jnsTindakan_ids' => 'Mapping tindakan NICU wajib dipilih sebelum generate.',
            ]);
        }

        $source = $this->repository->getSourceData($periode, $jenisIcu, $config);

        if ($strict && ($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'jnsTindakan_id' => 'Mapping tindakan NICU belum memiliki rincian tindakan.',
            ]);
        }

        $grandTotal = (int) $source['grand_total'];
        $perawatRecipients = $config['recipients']['perawat_nicu'] ?? [];
        $perawatIcu = $this->portion($grandTotal, $config['perawat_nicu_percent']);
        $pegawaiKhusus = $this->portion($perawatIcu, $config['pegawai_nicu_khusus_percent']);
        $perawatIcuReguler = $this->portion($perawatIcu, $this->regularNicuPercent($config));
        $perawatIcuDivider = max(1, (int) ($config['perawat_nicu_divider'] ?? 4));
        $perawatIcuPerOrang = (int) round($perawatIcuReguler / $perawatIcuDivider);
        $premiMedisPool = $this->portion($grandTotal, $config['premi_medis_percent']);
        $premiMedisPerOrang = (int) round($premiMedisPool / max(1, $config['premi_medis_divider']));
        $premiBersama = $this->portion($grandTotal, $config['premi_bersama_percent']);
        $pools = [
            'perawat_nicu_pool' => $perawatIcu,
            'perawat_nicu_divider' => $perawatIcuDivider,
            'perawat_nicu_per_orang' => $perawatIcuPerOrang,
            'perawat_nicu' => $perawatIcu,
            'perawat_nicu_reguler' => $perawatIcuReguler,
            'perawat_nicu_reguler_percent' => $this->regularNicuPercent($config),
            'pegawai_nicu_khusus' => $pegawaiKhusus,
            'premi_medis_pool' => $premiMedisPool,
            'premi_medis_per_orang' => $premiMedisPerOrang,
            'premi_bersama' => $premiBersama,
        ];
        $recipients = array_merge(
            $this->fixedRecipients(
                'perawat_nicu',
                $pools['perawat_nicu_reguler'],
                $pools['perawat_nicu_divider'],
                $this->regularPerawatNicuAllocationPercent($config),
                $perawatRecipients
            ),
            $this->splitPool(
                'pegawai_nicu_khusus',
                $pools['pegawai_nicu_khusus'],
                $config['recipients']['pegawai_nicu_khusus'] ?? []
            )
        );
        $warnings = $this->warnings($source, $config, $pools);

        if ($strict && ! empty($warnings['blocking'])) {
            throw ValidationException::withMessages([
                'config' => implode(' ', $warnings['blocking']),
            ]);
        }

        $configSnapshot = [
            ...$config,
            'source_periode' => $source['source_periode'],
            'source_tgl_awal' => $source['source_tgl_awal'],
            'source_tgl_akhir' => $source['source_tgl_akhir'],
            'mapping' => $source['mapping'],
            'pools' => $pools,
            'recipient_counts' => [
                'perawat_nicu' => count($config['recipients']['perawat_nicu'] ?? []),
                'pegawai_nicu_khusus' => count($config['recipients']['pegawai_nicu_khusus'] ?? []),
            ],
        ];

        return [
            'periode' => $periode,
            'jenis_nicu' => $jenisIcu,
            'jenis_nicu_label' => $this->typeLabel($jenisIcu),
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

        if (empty($config['jnsTindakan_ids'])) {
            $blocking[] = 'Mapping tindakan NICU belum dipilih.';
        } elseif (($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            $blocking[] = 'Mapping tindakan NICU belum memiliki rincian tindakan.';
        }

        $perawatRecipientCount = count($config['recipients']['perawat_nicu'] ?? []);
        $perawatDivider = max(1, (int) ($config['perawat_nicu_divider'] ?? ($pools['perawat_nicu_divider'] ?? 4)));
        $icuDistributionPercent = (float) ($config['pegawai_nicu_khusus_percent'] ?? 25)
            + $this->regularNicuPercent($config);

        if ($icuDistributionPercent > 100) {
            $blocking[] = 'Total persentase Pegawai NICU Khusus dan NICU Reguler tidak boleh melebihi 100% dari pool Perawat NICU.';
        }

        if (($pools['perawat_nicu_reguler'] ?? 0) > 0 && $perawatRecipientCount < 1) {
            $blocking[] = 'Pegawai penerima premi perawat NICU wajib dipilih.';
        }

        if ($pools['pegawai_nicu_khusus'] > 0 && empty($config['recipients']['pegawai_nicu_khusus'])) {
            $blocking[] = 'Pegawai penerima premi NICU khusus wajib dipilih.';
        }

        if ($source['jumlah_pasien_sumber'] < 1) {
            $info[] = 'Tidak ada pasien rawat inap pada periode sumber dan penjamin ini.';
        } elseif ($source['jumlah_pasien_nicu'] < 1) {
            $info[] = 'Ada pasien rawat inap, tetapi tidak ditemukan riwayat kamar NICU.';
        } elseif ($source['jumlah_tindakan'] < 1) {
            $info[] = 'Riwayat NICU ditemukan, tetapi belum ada tindakan yang cocok dengan mapping atau tindakan kritikal.';
        }

        return [
            'blocking' => $blocking,
            'info' => $info,
        ];
    }

    private function configFormPayload(array $data): array
    {
        $criticalActionNames = $this->criticalActionNamesFromInput($data);
        $pegawaiKhususPercent = (float) ($data['pegawai_nicu_khusus_percent'] ?? 25);
        $perawatRegulerPercent = (float) ($data['perawat_nicu_reguler_percent'] ?? 75);

        if (($pegawaiKhususPercent + $perawatRegulerPercent) > 100) {
            throw ValidationException::withMessages([
                'perawat_nicu_reguler_percent' => 'Total persentase Pegawai NICU Khusus dan NICU Reguler tidak boleh melebihi 100% dari pool Perawat NICU.',
            ]);
        }

        return [
            'perawat_nicu_percent' => (float) ($data['perawat_nicu_percent'] ?? 30),
            'pegawai_nicu_khusus_percent' => $pegawaiKhususPercent,
            'perawat_nicu_reguler_percent' => $perawatRegulerPercent,
            'perawat_nicu_divider' => max(1, (int) ($data['perawat_nicu_divider'] ?? 4)),
            'premi_medis_percent' => (float) ($data['premi_medis_percent'] ?? 25),
            'premi_medis_divider' => max(1, (int) ($data['premi_medis_divider'] ?? 34)),
            'premi_bersama_percent' => (float) ($data['premi_bersama_percent'] ?? 15),
            'critical_action_name' => $criticalActionNames[0] ?? '',
            'critical_action_names' => $criticalActionNames,
        ];
    }

    private function configPayload($config): array
    {
        $mappingItems = $config->relationLoaded('tindakan')
            ? $config->tindakan
                ->map(fn ($item) => $item->jenisTindakan)
                ->filter()
                ->map(fn ($jenis) => [
                    'id' => (int) $jenis->id,
                    'kode' => $jenis->kode,
                    'jenis' => $jenis->jenis,
                    'label' => trim($jenis->kode.' - '.$jenis->jenis),
                    'text' => trim($jenis->kode.' - '.$jenis->jenis),
                ])
                ->values()
            : collect();

        if ($mappingItems->isEmpty() && $config->jenisTindakan) {
            $mappingItems = collect([[
                'id' => (int) $config->jenisTindakan->id,
                'kode' => $config->jenisTindakan->kode,
                'jenis' => $config->jenisTindakan->jenis,
                'label' => trim($config->jenisTindakan->kode.' - '.$config->jenisTindakan->jenis),
                'text' => trim($config->jenisTindakan->kode.' - '.$config->jenisTindakan->jenis),
            ]]);
        }

        $recipients = [
            'perawat_nicu' => [],
            'pegawai_nicu_khusus' => [],
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

        $firstMapping = $mappingItems->first();
        $mappingLabel = $mappingItems->count() > 1
            ? $firstMapping['label'].' + '.($mappingItems->count() - 1).' mapping'
            : ($firstMapping['label'] ?? null);
        $criticalActionNames = collect($config->critical_action_names ?: [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?: ''))
            ->values();

        if ($criticalActionNames->isEmpty() && trim((string) $config->critical_action_name) !== '') {
            $criticalActionNames = collect([trim((string) $config->critical_action_name)]);
        }

        $firstCriticalAction = $criticalActionNames->first();
        $criticalActionLabel = $criticalActionNames->count() > 1
            ? $firstCriticalAction.' + '.($criticalActionNames->count() - 1).' tindakan'
            : ($firstCriticalAction ?? null);

        return [
            'id' => $config->id,
            'jenis_nicu' => $config->jenis_nicu,
            'jenis_nicu_label' => $this->typeLabel($config->jenis_nicu),
            'jnsTindakan_id' => $config->jnsTindakan_id,
            'jnsTindakan_ids' => $mappingItems->pluck('id')->all(),
            'mapping_items' => $mappingItems->all(),
            'kode_jenis_tindakan' => $config->jenisTindakan?->kode,
            'nama_jenis_tindakan' => $config->jenisTindakan?->jenis,
            'mapping_label' => $mappingLabel,
            'perawat_nicu_percent' => (float) $config->perawat_nicu_percent,
            'pegawai_nicu_khusus_percent' => (float) $config->pegawai_nicu_khusus_percent,
            'perawat_nicu_reguler_percent' => $this->regularNicuPercent([
                'pegawai_nicu_khusus_percent' => (float) $config->pegawai_nicu_khusus_percent,
                'perawat_nicu_reguler_percent' => $config->perawat_nicu_reguler_percent,
            ]),
            'perawat_nicu_divider' => max(1, (int) ($config->perawat_nicu_divider ?: 4)),
            'premi_medis_percent' => (float) $config->premi_medis_percent,
            'premi_medis_divider' => (int) $config->premi_medis_divider,
            'premi_bersama_percent' => (float) $config->premi_bersama_percent,
            'critical_action_name' => $firstCriticalAction ?? $config->critical_action_name,
            'critical_action_names' => $criticalActionNames->all(),
            'critical_action_label' => $criticalActionLabel,
            'recipients' => $recipients,
            'role_labels' => self::ROLE_LABELS,
        ];
    }

    private function resultPayload($row): array
    {
        $configSnapshot = $row->config_snapshot ?: [];

        return [
            'id' => $row->id,
            'periode' => $row->periode,
            'source_periode' => $row->source_periode,
            'source_tgl_awal' => optional($row->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($row->source_tgl_akhir)->format('Y-m-d'),
            'jenis_nicu' => $row->jenis_nicu,
            'jenis_nicu_label' => $this->typeLabel($row->jenis_nicu),
            'jnsTindakan_id' => $row->jnsTindakan_id,
            'kode_jenis_tindakan' => $row->kode_jenis_tindakan,
            'nama_jenis_tindakan' => $row->nama_jenis_tindakan,
            'mapping_label' => $configSnapshot['mapping']['label']
                ?? ($row->nama_jenis_tindakan
                    ? trim(($row->kode_jenis_tindakan ? $row->kode_jenis_tindakan.' - ' : '').$row->nama_jenis_tindakan)
                    : '-'),
            'jumlah_pasien_sumber' => $row->jumlah_pasien_sumber,
            'jumlah_pasien_nicu' => $row->jumlah_pasien_nicu,
            'jumlah_pasien' => $row->jumlah_pasien,
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'jumlah_tindakan_nicu' => $row->jumlah_tindakan_nicu,
            'jumlah_tindakan_kritikal' => $row->jumlah_tindakan_kritikal,
            'grand_total' => $row->grand_total,
            'total_perawat_nicu' => $row->total_perawat_nicu,
            'total_perawat_nicu_reguler' => $row->total_perawat_nicu_reguler,
            'total_pegawai_nicu_khusus' => $row->total_pegawai_nicu_khusus,
            'total_premi_medis_pool' => $row->total_premi_medis_pool,
            'premi_medis_per_orang' => $row->premi_medis_per_orang,
            'total_premi_bersama' => $row->total_premi_bersama,
            'details_count' => $row->details_count ?? $row->details?->count() ?? 0,
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
            'source_table' => $detail->source_table,
            'sumber_tindakan' => $detail->sumber_tindakan,
            'source_label' => $this->sourceLabel($detail->source_table),
            'no_rawat' => $detail->no_rawat,
            'no_rkm_medis' => $detail->no_rkm_medis,
            'nm_pasien' => $detail->nm_pasien,
            'kd_pj' => $detail->kd_pj,
            'nama_penjamin' => $detail->nama_penjamin,
            'kd_kamar_nicu' => $detail->kd_kamar_nicu,
            'tgl_masuk_nicu' => optional($detail->tgl_masuk_nicu)->format('Y-m-d'),
            'jam_masuk_nicu' => $detail->jam_masuk_nicu,
            'tgl_keluar_nicu' => optional($detail->tgl_keluar_nicu)->format('Y-m-d'),
            'jam_keluar_nicu' => $detail->jam_keluar_nicu,
            'tanggal' => optional($detail->tanggal)->format('Y-m-d'),
            'jam' => $detail->jam,
            'kd_tindakan' => $detail->kd_tindakan,
            'nm_tindakan' => $detail->nm_tindakan,
            'kd_dokter' => $detail->kd_dokter,
            'nm_dokter' => $detail->nm_dokter,
            'nip' => $detail->nip,
            'nama_petugas' => $detail->nama_petugas,
            'biaya_rawat' => $detail->biaya_rawat,
            'is_in_nicu_range' => $detail->is_in_nicu_range,
            'is_critical_action' => $detail->is_critical_action,
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

    private function regularPerawatNicuAllocationPercent(array $config): float
    {
        $divider = max(1, (int) ($config['perawat_nicu_divider'] ?? 4));

        return round($this->regularNicuPercent($config) / $divider, 2);
    }

    private function regularNicuPercent(array $config): float
    {
        if (
            array_key_exists('perawat_nicu_reguler_percent', $config)
            && $config['perawat_nicu_reguler_percent'] !== null
        ) {
            return max(0, (float) $config['perawat_nicu_reguler_percent']);
        }

        return max(0, 100 - (float) ($config['pegawai_nicu_khusus_percent'] ?? 25));
    }

    private function criticalActionNamesFromInput(array $data): array
    {
        $names = collect($data['critical_action_names'] ?? []);

        if ($names->isEmpty() && array_key_exists('critical_action_name', $data)) {
            $names = collect([$data['critical_action_name']]);
        }

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?: ''))
            ->values()
            ->all();
    }

    private function hydrateRecipients(array $input): array
    {
        $result = [
            'perawat_nicu' => [],
            'pegawai_nicu_khusus' => [],
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
        $perawatIcuDivider = data_get($configSnapshot, 'pools.perawat_nicu_divider')
            ?? data_get($configSnapshot, 'perawat_nicu_divider')
            ?? 4;
        $perawatIcuRegulerPercent = data_get($configSnapshot, 'pools.perawat_nicu_reguler_percent')
            ?? data_get($configSnapshot, 'perawat_nicu_reguler_percent')
            ?? max(0, 100 - (float) data_get($configSnapshot, 'pegawai_nicu_khusus_percent', 25));
        $perawatIcuPerOrang = data_get($configSnapshot, 'pools.perawat_nicu_per_orang');

        if ($perawatIcuPerOrang === null && $row->relationLoaded('recipients')) {
            $perawatIcuPerOrang = $row->recipients
                ->firstWhere('role', 'perawat_nicu')
                ?->total_received;
        }

        return [
            'perawat_nicu_pool' => (int) (data_get($configSnapshot, 'pools.perawat_nicu_pool') ?? $row->total_perawat_nicu),
            'perawat_nicu_divider' => (int) $perawatIcuDivider,
            'perawat_nicu_per_orang' => (int) ($perawatIcuPerOrang ?? 0),
            'perawat_nicu_reguler_percent' => (float) $perawatIcuRegulerPercent,
            'perawat_nicu' => $row->total_perawat_nicu,
            'perawat_nicu_reguler' => $row->total_perawat_nicu_reguler,
            'pegawai_nicu_khusus' => $row->total_pegawai_nicu_khusus,
            'premi_medis_pool' => $row->total_premi_medis_pool,
            'premi_medis_per_orang' => $row->premi_medis_per_orang,
            'premi_bersama' => $row->total_premi_bersama,
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

    private function typeLabel(string $jenisIcu): string
    {
        return $jenisIcu === 'bpjs' ? 'BPJS Kesehatan' : 'Umum';
    }
}


