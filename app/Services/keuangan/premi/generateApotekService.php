<?php

namespace App\Services\keuangan\premi;

use App\Models\User;
use App\Repositories\keuangan\premi\generateApotekRepository;
use App\Support\PremiSourcePeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generateApotekService
{
    private const CATEGORY_APOTEK = 'apotek';

    private const CATEGORY_APOTEKER = 'apoteker';

    private const ROLE_LABELS = [
        'penerima_31' => 'Penerima 31% / 2.5',
        'penerima_7' => 'Penerima 7%',
        'penerima_12' => 'Penerima 12% / 2',
    ];

    public function __construct(
        protected generateApotekRepository $repository
    ) {}

    public function getResults(?string $periode = null, ?string $jenisApotek = null, string $kategoriPremi = self::CATEGORY_APOTEK)
    {
        return $this->repository
            ->getResults($periode, $jenisApotek, $this->normalizeCategory($kategoriPremi))
            ->map(fn ($row) => $this->resultPayload($row));
    }

    public function getSummary(?string $periode, string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): array
    {
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);
        $config = $this->configPayload($this->repository->getConfig($jenisApotek, $kategoriPremi));
        $sourcePeriodMode = $config['source_period_mode'];

        return [
            'periode' => $periode,
            'kategori_premi' => $kategoriPremi,
            'kategori_premi_label' => $this->categoryLabel($kategoriPremi),
            'jenis_apotek' => $jenisApotek,
            'jenis_apotek_label' => $this->typeLabel($jenisApotek),
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $config['source_period_mode_label'],
            'include_bpjs_in_umum' => $config['include_bpjs_in_umum'],
            'source_periode' => $periode
                ? PremiSourcePeriod::resolve($periode, $jenisApotek, $sourcePeriodMode)
                : null,
            ...$this->repository->getSummary($periode, $jenisApotek, $kategoriPremi),
        ];
    }

    public function getConfig(string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): array
    {
        return $this->configPayload($this->repository->getConfig($jenisApotek, $this->normalizeCategory($kategoriPremi)));
    }

    public function updateConfig(string $jenisApotek, array $data, string $kategoriPremi = self::CATEGORY_APOTEK): array
    {
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);
        $data['kategori_premi'] = $kategoriPremi;
        $mappingIds = collect($data['jnsTindakan_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $recipients = $kategoriPremi === self::CATEGORY_APOTEK
            ? $this->hydrateRecipients($data['recipients'] ?? [])
            : [];

        return $this->configPayload(
            $this->repository->saveConfig(
                $jenisApotek,
                $kategoriPremi,
                $this->configFormPayload($data),
                $mappingIds,
                $recipients
            )
        );
    }

    public function mappingOptions(?string $keyword = null, string $kategoriPremi = self::CATEGORY_APOTEK)
    {
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);

        return $this->repository
            ->mappingOptions($keyword, $kategoriPremi)
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'kode' => $item->kode,
                'jenis' => $item->jenis,
                'jumlah_mapping' => (int) $item->jumlah_mapping,
                'pembagi' => (int) ($item->pembagi ?? 1),
                'text' => $kategoriPremi === self::CATEGORY_APOTEKER
                    ? trim($item->kode.' - '.$item->jenis.' ('.$item->jumlah_mapping.' tindakan / pembagi '.max(1, (int) ($item->pembagi ?? 1)).')')
                    : trim($item->kode.' - '.$item->jenis.' ('.$item->jumlah_mapping.' obat)'),
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

    public function preview(string $periode, string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): array
    {
        return $this->calculate($periode, $jenisApotek, false, $this->normalizeCategory($kategoriPremi));
    }

    public function generate(string $periode, string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): array
    {
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);

        return DB::transaction(function () use ($periode, $jenisApotek, $kategoriPremi) {
            $existing = $this->repository->findExistingForUpdate($periode, $jenisApotek, $kategoriPremi);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => 'Data '.$this->categoryLabel($kategoriPremi).' periode dan jenis ini sudah dikunci.',
                ]);
            }

            $calculation = $this->calculate($periode, $jenisApotek, true, $kategoriPremi);

            return $this->resultPayload(
                $this->repository->saveResult($periode, $jenisApotek, $calculation)
            );
        });
    }

    public function detail(int $id): array
    {
        $result = $this->repository->findWithDetails($id);

        if (! $result) {
            abort(404, 'Data generate Apotek tidak ditemukan.');
        }

        $payload = $this->resultPayload($result);
        $payload['source'] = [
            'source_periode' => $result->source_periode,
            'source_period_mode' => $payload['source_period_mode'],
            'source_period_mode_label' => $payload['source_period_mode_label'],
            'source_tgl_awal' => optional($result->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($result->source_tgl_akhir)->format('Y-m-d'),
            'jumlah_data_sumber' => $result->jumlah_data_sumber,
            'jumlah_pasien_sumber' => $result->jumlah_pasien_sumber,
            'jumlah_obat_sumber' => $result->jumlah_obat_sumber,
            'jumlah_data_mapping' => $result->jumlah_data_mapping,
            'jumlah_pasien' => $result->jumlah_pasien,
            'jumlah_obat' => $result->jumlah_obat,
            'jumlah_tindakan' => $result->jumlah_tindakan,
            'jumlah_mapping_premi' => $result->jumlah_mapping_premi,
            'total_qty' => $result->total_qty,
            'tarif_per_item' => $result->tarif_per_item,
            'grand_total' => $result->grand_total,
            'total_biaya_rawat' => $result->total_biaya_rawat,
            'total_mapping_premi' => $result->total_mapping_premi,
            'pembagi' => $payload['pembagi'] ?? 1,
            'total_final' => $result->total_final,
        ];
        $payload['pools'] = $this->poolPayload($result);
        $payload['calculation_details'] = data_get($result->config_snapshot, 'calculation_details', []);
        $payload['recipient_groups'] = collect($payload['recipients'])
            ->groupBy('role')
            ->map(fn ($items) => [
                'role' => $items->first()['role'],
                'role_label' => $items->first()['role_label'],
                'pool_total' => $items->first()['pool_total'],
                'amount_per_recipient' => $items->first()['amount_per_recipient'],
                'recipient_count' => $items->count(),
                'total_received' => $items->sum('total_received'),
                'items' => $items->values(),
            ])
            ->values();
        $payload['tindakan_groups'] = collect($payload['details'])
            ->filter(fn ($item) => ! empty($item['kd_tindakan']))
            ->groupBy('mapping_premi_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'mapping_premi_id' => $first['mapping_premi_id'],
                    'kd_tindakan' => $first['kd_tindakan'],
                    'nm_tindakan' => $first['nm_tindakan'] ?: $first['nama_barang'],
                    'jenis_mapping' => $first['jenis_mapping'],
                    'nilai_mapping' => $first['nilai_mapping'],
                    'jumlah_item' => $items->count(),
                    'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => $items->sum('biaya_rawat'),
                    'total_premi' => $items->sum('total_premi'),
                ];
            })
            ->sortByDesc('total_biaya_rawat')
            ->values();
        $payload['source_groups'] = collect($payload['details'])
            ->groupBy('status')
            ->map(fn ($items) => [
                'status' => $items->first()['status'] ?: '-',
                'jumlah_item' => $items->count(),
                'total_premi' => $items->sum('total_premi'),
            ])
            ->values();
        $payload['penjamin_groups'] = collect($payload['details'])
            ->groupBy(fn ($item) => ($item['kd_pj'] ?: '-').'|'.($item['nama_penjamin'] ?: '-'))
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'kd_pj' => $first['kd_pj'] ?: '-',
                    'nama_penjamin' => $first['nama_penjamin'] ?: '-',
                    'jumlah_item' => $items->count(),
                    'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
                    'jumlah_obat' => $items->pluck('kode_barang')->unique()->count(),
                    'total_qty' => round((float) $items->sum('qty'), 2),
                    'total_premi' => $items->sum('total_premi'),
                ];
            })
            ->sortByDesc('total_premi')
            ->values();

        return $payload;
    }

    public function lock(int $id, User $user): array
    {
        return DB::transaction(function () use ($id, $user) {
            $result = $this->repository->findForUpdate($id);

            if (! $result) {
                abort(404, 'Data generate Apotek tidak ditemukan.');
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
                abort(404, 'Data generate Apotek tidak ditemukan.');
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
                abort(404, 'Data generate Apotek tidak ditemukan.');
            }

            if ($result->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Data Apotek yang sudah terkunci tidak dapat dihapus.',
                ]);
            }

            $this->repository->deleteResult($result);
        });
    }

    private function calculate(
        string $periode,
        string $jenisApotek,
        bool $strict = false,
        string $kategoriPremi = self::CATEGORY_APOTEK
    ): array
    {
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);

        if ($kategoriPremi === self::CATEGORY_APOTEKER) {
            return $this->calculateApoteker($periode, $jenisApotek, $strict);
        }

        $config = $this->configPayload($this->repository->getConfig($jenisApotek, self::CATEGORY_APOTEK));

        if ($strict && empty($config['jnsTindakan_ids'])) {
            throw ValidationException::withMessages([
                'jnsTindakan_ids' => 'Mapping Farmasi wajib dipilih sebelum generate.',
            ]);
        }

        $source = $this->repository->getSourceData($periode, $jenisApotek, $config);

        if ($strict && ($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'jnsTindakan_ids' => 'Mapping Farmasi yang dipilih belum memiliki rincian obat.',
            ]);
        }

        $grandTotal = (int) $source['grand_total'];
        $formulaBasePercent = 50.0;
        $formulaBase = $this->portion($grandTotal, $formulaBasePercent);
        $formula31Pool = $this->portion($formulaBase, $config['formula_31_percent']);
        $formula7Pool = $this->portion($formulaBase, $config['formula_7_percent']);
        $formula12Pool = $this->portion($formulaBase, $config['formula_12_percent']);
        $formula31Amount = $this->divideAmount($formula31Pool, $config['formula_31_divider']);
        $formula7Amount = $this->divideAmount($formula7Pool, $config['formula_7_divider']);
        $formula12Amount = $this->divideAmount($formula12Pool, $config['formula_12_divider']);
        $premiBersama = $this->portion($grandTotal, $config['premi_bersama_percent']);
        $recipientInputs = $config['recipients'];
        $recipients = array_merge(
            $this->fixedRecipients(
                'penerima_31',
                $formula31Pool,
                $formula31Amount,
                $config['formula_31_divider'],
                $this->grandAllocationPercent($formulaBasePercent, $config['formula_31_percent'], $config['formula_31_divider']),
                $recipientInputs['penerima_31'] ?? []
            ),
            $this->fixedRecipients(
                'penerima_7',
                $formula7Pool,
                $formula7Amount,
                $config['formula_7_divider'],
                $this->grandAllocationPercent($formulaBasePercent, $config['formula_7_percent'], $config['formula_7_divider']),
                $recipientInputs['penerima_7'] ?? []
            ),
            $this->fixedRecipients(
                'penerima_12',
                $formula12Pool,
                $formula12Amount,
                $config['formula_12_divider'],
                $this->grandAllocationPercent($formulaBasePercent, $config['formula_12_percent'], $config['formula_12_divider']),
                $recipientInputs['penerima_12'] ?? []
            )
        );
        $pools = [
            'jasa_farmasi_pool' => $formulaBase,
            'formula_base_percent' => $formulaBasePercent,
            'formula_base_total' => $formulaBase,
            'formula_31_pool' => $formula31Pool,
            'formula_31_divider' => (float) $config['formula_31_divider'],
            'formula_31_per_penerima' => $formula31Amount,
            'formula_31_total' => collect($recipients)->where('role', 'penerima_31')->sum('total_received'),
            'formula_7_pool' => $formula7Pool,
            'formula_7_divider' => (float) $config['formula_7_divider'],
            'formula_7_per_penerima' => $formula7Amount,
            'formula_7_total' => collect($recipients)->where('role', 'penerima_7')->sum('total_received'),
            'formula_12_pool' => $formula12Pool,
            'formula_12_divider' => (float) $config['formula_12_divider'],
            'formula_12_per_penerima' => $formula12Amount,
            'formula_12_total' => collect($recipients)->where('role', 'penerima_12')->sum('total_received'),
            'premi_bersama' => $premiBersama,
        ];
        $warnings = $this->warnings($source, $config, $pools);

        if ($strict && ! empty($warnings['blocking'])) {
            throw ValidationException::withMessages([
                'config' => implode(' ', $warnings['blocking']),
            ]);
        }

        $configSnapshot = [
            ...$config,
            'jasa_farmasi_percent' => $formulaBasePercent,
            'source_periode' => $source['source_periode'],
            'source_period_mode' => $source['source_period_mode'],
            'source_period_mode_label' => $source['source_period_mode_label'],
            'source_tgl_awal' => $source['source_tgl_awal'],
            'source_tgl_akhir' => $source['source_tgl_akhir'],
            'mapping' => $source['mapping'],
            'pools' => $pools,
            'recipient_counts' => [
                'penerima_31' => count($recipientInputs['penerima_31'] ?? []),
                'penerima_7' => count($recipientInputs['penerima_7'] ?? []),
                'penerima_12' => count($recipientInputs['penerima_12'] ?? []),
            ],
        ];

        return [
            'kategori_premi' => self::CATEGORY_APOTEK,
            'kategori_premi_label' => $this->categoryLabel(self::CATEGORY_APOTEK),
            'periode' => $periode,
            'jenis_apotek' => $jenisApotek,
            'jenis_apotek_label' => $this->typeLabel($jenisApotek),
            'source_periode' => $source['source_periode'],
            'source_period_mode' => $source['source_period_mode'],
            'source_period_mode_label' => $source['source_period_mode_label'],
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
                    'amount_per_recipient' => $items->first()['amount_per_recipient'],
                    'recipient_count' => $items->count(),
                    'total_received' => $items->sum('total_received'),
                    'items' => $items->values(),
                ])
                ->values(),
            'total_dibagikan' => collect($recipients)->sum('total_received'),
            'config' => $configSnapshot,
            'config_snapshot' => $configSnapshot,
            'warnings' => $warnings,
            'can_generate' => empty($warnings['blocking']),
        ];
    }

    private function calculateApoteker(string $periode, string $jenisApotek, bool $strict = false): array
    {
        $config = $this->configPayload($this->repository->getConfig($jenisApotek, self::CATEGORY_APOTEKER));

        if ($strict && empty($config['jnsPremi_id'])) {
            throw ValidationException::withMessages([
                'jnsPremi_id' => 'Jenis premi Apoteker wajib dipilih sebelum generate.',
            ]);
        }

        $source = $this->repository->getApotekerSourceData($periode, $jenisApotek, $config);

        if ($strict && ($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'jnsPremi_id' => 'Jenis premi Apoteker belum memiliki mapping tindakan rawat.',
            ]);
        }

        $recipients = $this->apotekerRecipients(
            (int) $source['total_final'],
            (int) $source['pembagi'],
            (int) ($config['jnsPremi_id'] ?? 0)
        );
        $pools = [
            'grand_total' => (int) $source['grand_total'],
            'total_biaya_rawat' => (int) $source['total_biaya_rawat'],
            'total_mapping_premi' => (int) $source['total_mapping_premi'],
            'pembagi' => (int) $source['pembagi'],
            'total_final' => (int) $source['total_final'],
            'total_dibagikan' => (int) collect($recipients)->sum('total_received'),
        ];
        $warnings = $this->apotekerWarnings($source, $config, $recipients);

        if ($strict && ! empty($warnings['blocking'])) {
            throw ValidationException::withMessages([
                'config' => implode(' ', $warnings['blocking']),
            ]);
        }

        $configSnapshot = [
            ...$config,
            'source_periode' => $source['source_periode'],
            'source_period_mode' => $source['source_period_mode'],
            'source_period_mode_label' => $source['source_period_mode_label'],
            'source_tgl_awal' => $source['source_tgl_awal'],
            'source_tgl_akhir' => $source['source_tgl_akhir'],
            'mapping' => $source['mapping'],
            'calculation_details' => $source['calculation_details']->all(),
            'pools' => $pools,
            'recipient_counts' => [
                'apoteker' => count($recipients),
            ],
        ];

        return [
            'kategori_premi' => self::CATEGORY_APOTEKER,
            'kategori_premi_label' => $this->categoryLabel(self::CATEGORY_APOTEKER),
            'periode' => $periode,
            'jenis_apotek' => $jenisApotek,
            'jenis_apotek_label' => $this->typeLabel($jenisApotek),
            'source_periode' => $source['source_periode'],
            'source_period_mode' => $source['source_period_mode'],
            'source_period_mode_label' => $source['source_period_mode_label'],
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
                    'amount_per_recipient' => $items->first()['amount_per_recipient'],
                    'recipient_count' => $items->count(),
                    'total_received' => $items->sum('total_received'),
                    'items' => $items->values(),
                ])
                ->values(),
            'total_dibagikan' => (int) collect($recipients)->sum('total_received'),
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
            $blocking[] = 'Mapping Farmasi belum dipilih.';
        } elseif (($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            $blocking[] = 'Mapping Farmasi belum memiliki rincian obat.';
        }

        $formulaTotal = (float) $config['formula_31_percent']
            + (float) $config['formula_7_percent']
            + (float) $config['formula_12_percent'];

        if ($formulaTotal > 100) {
            $blocking[] = 'Total formula 31%, 7%, dan 12% tidak boleh melebihi 100% dari pool 50%.';
        }

        foreach (self::ROLE_LABELS as $role => $label) {
            $poolKey = match ($role) {
                'penerima_31' => 'formula_31_per_penerima',
                'penerima_7' => 'formula_7_per_penerima',
                default => 'formula_12_per_penerima',
            };

            if (($pools[$poolKey] ?? 0) > 0 && empty($config['recipients'][$role])) {
                $blocking[] = $label.' wajib dipilih.';
            }
        }

        if ($source['jumlah_data_sumber'] < 1) {
            $info[] = 'Tidak ada data detail_pemberian_obat pada periode sumber dan penjamin ini.';
        } elseif ($source['jumlah_data_mapping'] < 1) {
            $info[] = 'Ada data obat, tetapi belum ada kode barang yang cocok dengan mapping Farmasi pilihan.';
        }

        return [
            'blocking' => $blocking,
            'info' => $info,
        ];
    }

    private function apotekerWarnings(array $source, array $config, array $recipients): array
    {
        $blocking = [];
        $info = [];

        if (empty($config['jnsPremi_id'])) {
            $blocking[] = 'Jenis premi Apoteker belum dipilih.';
        } elseif (($source['mapping']['jumlah_mapping'] ?? 0) < 1) {
            $blocking[] = 'Jenis premi Apoteker belum memiliki mapping tindakan rawat.';
        }

        if (($source['total_final'] ?? 0) > 0 && empty($recipients)) {
            $blocking[] = 'Pegawai penerima pada mapping premi Apoteker belum diisi.';
        }

        if ($source['jumlah_data_sumber'] < 1) {
            $info[] = 'Tidak ada tindakan rawat pada periode sumber dan penjamin ini.';
        } elseif ($source['jumlah_data_mapping'] < 1) {
            $info[] = 'Ada tindakan rawat, tetapi belum ada tindakan yang cocok dengan mapping premi Apoteker.';
        }

        return [
            'blocking' => $blocking,
            'info' => $info,
        ];
    }

    private function apotekerRecipients(int $pool, int $divider, int $jnsPremiId): array
    {
        $items = $this->repository->premiRecipients($jnsPremiId);

        if ($items->isEmpty()) {
            return [];
        }

        $recipientCount = $items->count();
        $base = intdiv($pool, $recipientCount);
        $remainder = $pool % $recipientCount;
        $allocationPercent = $pool > 0 ? round(100 / $recipientCount, 2) : null;

        return $items
            ->values()
            ->map(fn ($item, $index) => [
                'role' => 'apoteker',
                'role_label' => 'Pegawai Mapping Premi',
                'pegawai_id' => $item->pegawai_id,
                'pegawai_name' => $item->pegawai_name,
                'pegawai_position' => $item->pegawai_position ?? null,
                'allocation_percent' => $allocationPercent,
                'divider' => max(1, $divider),
                'pool_total' => $pool,
                'amount_per_recipient' => $base,
                'total_received' => $base + ($index < $remainder ? 1 : 0),
            ])
            ->all();
    }

    private function configFormPayload(array $data): array
    {
        $kategoriPremi = $this->normalizeCategory($data['kategori_premi'] ?? null);
        $formulaTotal = (float) ($data['formula_31_percent'] ?? 31)
            + (float) ($data['formula_7_percent'] ?? 7)
            + (float) ($data['formula_12_percent'] ?? 12);

        if ($formulaTotal > 100) {
            throw ValidationException::withMessages([
                'formula_12_percent' => 'Total formula 31%, 7%, dan 12% tidak boleh melebihi 100% dari pool 50%.',
            ]);
        }

        return [
            'kategori_premi' => $kategoriPremi,
            'jnsPremi_id' => $kategoriPremi === self::CATEGORY_APOTEKER
                ? (int) ($data['jnsPremi_id'] ?? 0) ?: null
                : null,
            'tarif_per_item' => max(0, (int) ($data['tarif_per_item'] ?? 500)),
            'source_period_mode' => PremiSourcePeriod::normalizeMode(
                $data['source_period_mode'] ?? null,
                (string) ($data['jenis_apotek'] ?? '')
            ),
            'include_bpjs_in_umum' => ($data['jenis_apotek'] ?? '') === 'umum'
                && filter_var($data['include_bpjs_in_umum'] ?? false, FILTER_VALIDATE_BOOL),
            'jasa_farmasi_percent' => 50,
            'formula_31_percent' => (float) ($data['formula_31_percent'] ?? 31),
            'formula_31_divider' => max(0.01, (float) ($data['formula_31_divider'] ?? 2.5)),
            'formula_7_percent' => (float) ($data['formula_7_percent'] ?? 7),
            'formula_7_divider' => max(0.01, (float) ($data['formula_7_divider'] ?? 1)),
            'formula_12_percent' => (float) ($data['formula_12_percent'] ?? 12),
            'formula_12_divider' => max(0.01, (float) ($data['formula_12_divider'] ?? 2)),
            'premi_bersama_percent' => (float) ($data['premi_bersama_percent'] ?? 30),
        ];
    }

    private function configPayload($config): array
    {
        $mappingItems = $config->relationLoaded('mappings')
            ? $config->mappings
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
            'penerima_31' => [],
            'penerima_7' => [],
            'penerima_12' => [],
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
        $kategoriPremi = $this->normalizeCategory($config->kategori_premi ?? null);
        $premiItem = $config->jnsPremi ? [
            'id' => (int) $config->jnsPremi->id,
            'kode' => $config->jnsPremi->kode,
            'jenis' => $config->jnsPremi->jenis,
            'pembagi' => max(1, (int) ($config->jnsPremi->pembagi ?? 1)),
            'label' => trim($config->jnsPremi->kode.' - '.$config->jnsPremi->jenis),
            'text' => trim($config->jnsPremi->kode.' - '.$config->jnsPremi->jenis),
        ] : null;

        return [
            'id' => $config->id,
            'kategori_premi' => $kategoriPremi,
            'kategori_premi_label' => $this->categoryLabel($kategoriPremi),
            'jenis_apotek' => $config->jenis_apotek,
            'jenis_apotek_label' => $this->typeLabel($config->jenis_apotek),
            'source_period_mode' => PremiSourcePeriod::normalizeMode(
                $config->source_period_mode,
                $config->jenis_apotek
            ),
            'source_period_mode_label' => PremiSourcePeriod::modeLabel(
                $config->source_period_mode,
                $config->jenis_apotek
            ),
            'include_bpjs_in_umum' => $config->jenis_apotek === 'umum'
                && (bool) ($config->include_bpjs_in_umum ?? false),
            'jnsTindakan_id' => $config->jnsTindakan_id,
            'jnsPremi_id' => $config->jnsPremi_id,
            'premi_item' => $premiItem,
            'jnsTindakan_ids' => $mappingItems->pluck('id')->all(),
            'mapping_items' => $mappingItems->all(),
            'kode_jenis_tindakan' => $config->jenisTindakan?->kode,
            'nama_jenis_tindakan' => $config->jenisTindakan?->jenis,
            'mapping_label' => $kategoriPremi === self::CATEGORY_APOTEKER
                ? ($premiItem['label'] ?? null)
                : $mappingLabel,
            'tarif_per_item' => (int) $config->tarif_per_item,
            'jasa_farmasi_percent' => 50.0,
            'formula_31_percent' => (float) $config->formula_31_percent,
            'formula_31_divider' => (float) $config->formula_31_divider,
            'formula_7_percent' => (float) $config->formula_7_percent,
            'formula_7_divider' => (float) $config->formula_7_divider,
            'formula_12_percent' => (float) $config->formula_12_percent,
            'formula_12_divider' => (float) $config->formula_12_divider,
            'premi_bersama_percent' => (float) $config->premi_bersama_percent,
            'recipients' => $recipients,
            'role_labels' => self::ROLE_LABELS,
        ];
    }

    private function resultPayload($row): array
    {
        $configSnapshot = $row->config_snapshot ?: [];
        $kategoriPremi = $this->normalizeCategory($row->kategori_premi ?? $configSnapshot['kategori_premi'] ?? null);
        $sourcePeriodMode = $configSnapshot['source_period_mode']
            ?? $row->source_period_mode
            ?? $this->sourcePeriodModeFromResult($row->periode, $row->source_periode, $row->jenis_apotek);

        return [
            'id' => $row->id,
            'kategori_premi' => $kategoriPremi,
            'kategori_premi_label' => $this->categoryLabel($kategoriPremi),
            'periode' => $row->periode,
            'source_periode' => $row->source_periode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => PremiSourcePeriod::modeLabel($sourcePeriodMode, $row->jenis_apotek),
            'include_bpjs_in_umum' => $row->jenis_apotek === 'umum'
                && (bool) ($configSnapshot['include_bpjs_in_umum'] ?? false),
            'source_tgl_awal' => optional($row->source_tgl_awal)->format('Y-m-d'),
            'source_tgl_akhir' => optional($row->source_tgl_akhir)->format('Y-m-d'),
            'jenis_apotek' => $row->jenis_apotek,
            'jenis_apotek_label' => $this->typeLabel($row->jenis_apotek),
            'jnsTindakan_id' => $row->jnsTindakan_id,
            'jnsPremi_id' => $row->jnsPremi_id,
            'kode_premi' => $row->kode_premi,
            'nama_premi' => $row->nama_premi,
            'pembagi' => max(1, (int) ($row->pembagi ?? data_get($configSnapshot, 'mapping.pembagi', 1))),
            'kode_jenis_tindakan' => $row->kode_jenis_tindakan,
            'nama_jenis_tindakan' => $row->nama_jenis_tindakan,
            'mapping_label' => $configSnapshot['mapping']['label']
                ?? ($row->nama_jenis_tindakan
                    ? trim(($row->kode_jenis_tindakan ? $row->kode_jenis_tindakan.' - ' : '').$row->nama_jenis_tindakan)
                    : '-'),
            'jumlah_data_sumber' => $row->jumlah_data_sumber,
            'jumlah_pasien_sumber' => $row->jumlah_pasien_sumber,
            'jumlah_obat_sumber' => $row->jumlah_obat_sumber,
            'jumlah_data_mapping' => $row->jumlah_data_mapping,
            'jumlah_pasien' => $row->jumlah_pasien,
            'jumlah_obat' => $row->jumlah_obat,
            'jumlah_tindakan' => $row->jumlah_tindakan,
            'jumlah_mapping_premi' => $row->jumlah_mapping_premi,
            'total_qty' => $row->total_qty,
            'tarif_per_item' => $row->tarif_per_item,
            'grand_total' => $row->grand_total,
            'total_biaya_rawat' => $row->total_biaya_rawat,
            'total_mapping_premi' => $row->total_mapping_premi,
            'total_final' => $row->total_final,
            'total_jasa_farmasi_pool' => $row->total_jasa_farmasi_pool,
            'total_formula_31' => $row->total_formula_31,
            'total_formula_7' => $row->total_formula_7,
            'total_formula_12' => $row->total_formula_12,
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
            'mapping_premi_id' => $detail->mapping_premi_id,
            'jnsTindakan_id' => $detail->jnsTindakan_id,
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
            'kode_barang' => $detail->kode_barang,
            'nama_barang' => $detail->nama_barang,
            'qty' => $detail->qty,
            'harga_obat' => $detail->harga_obat,
            'total_obat' => $detail->total_obat,
            'nominal_premi' => $detail->nominal_premi,
            'total_premi' => $detail->total_premi,
            'status' => $detail->status,
            'kd_tindakan' => $detail->kd_tindakan,
            'nm_tindakan' => $detail->nm_tindakan,
            'kd_dokter' => $detail->kd_dokter,
            'nm_dokter' => $detail->nm_dokter,
            'nip' => $detail->nip,
            'nama_petugas' => $detail->nama_petugas,
            'biaya_rawat' => $detail->biaya_rawat,
            'jenis_mapping' => $detail->jenis_mapping,
            'nilai_mapping' => $detail->nilai_mapping,
            'hasil_mapping' => $detail->hasil_mapping,
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
            'divider' => $recipient->divider,
            'pool_total' => $recipient->pool_total,
            'amount_per_recipient' => $recipient->amount_per_recipient,
            'total_received' => $recipient->total_received,
        ];
    }

    private function fixedRecipients(
        string $role,
        int $pool,
        int $amountPerRecipient,
        float $divider,
        float $allocationPercent,
        array $items
    ): array {
        if (empty($items)) {
            return [];
        }

        return collect($items)
            ->values()
            ->map(fn ($item) => [
                'role' => $role,
                'role_label' => self::ROLE_LABELS[$role],
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'] ?? null,
                'allocation_percent' => $allocationPercent,
                'divider' => $divider,
                'pool_total' => $pool,
                'amount_per_recipient' => $amountPerRecipient,
                'total_received' => $amountPerRecipient,
            ])
            ->all();
    }

    private function hydrateRecipients(array $input): array
    {
        $result = [
            'penerima_31' => [],
            'penerima_7' => [],
            'penerima_12' => [],
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

        if ($this->normalizeCategory($row->kategori_premi ?? null) === self::CATEGORY_APOTEKER) {
            return [
                'grand_total' => $row->grand_total,
                'total_biaya_rawat' => $row->total_biaya_rawat,
                'total_mapping_premi' => $row->total_mapping_premi,
                'pembagi' => max(1, (int) ($row->pembagi ?? data_get($configSnapshot, 'pools.pembagi', 1))),
                'total_final' => $row->total_final,
                'total_dibagikan' => $row->total_dibagikan,
                'calculation_details' => data_get($configSnapshot, 'calculation_details', []),
            ];
        }

        return [
            'jasa_farmasi_pool' => $row->total_jasa_farmasi_pool,
            'formula_base_percent' => (float) data_get($configSnapshot, 'pools.formula_base_percent', 50),
            'formula_base_total' => (int) data_get($configSnapshot, 'pools.formula_base_total', $row->total_jasa_farmasi_pool),
            'formula_31_pool' => (int) data_get($configSnapshot, 'pools.formula_31_pool', 0),
            'formula_31_divider' => (float) data_get($configSnapshot, 'pools.formula_31_divider', 2.5),
            'formula_31_per_penerima' => (int) data_get($configSnapshot, 'pools.formula_31_per_penerima', 0),
            'formula_31_total' => $row->total_formula_31,
            'formula_7_pool' => (int) data_get($configSnapshot, 'pools.formula_7_pool', 0),
            'formula_7_divider' => (float) data_get($configSnapshot, 'pools.formula_7_divider', 1),
            'formula_7_per_penerima' => (int) data_get($configSnapshot, 'pools.formula_7_per_penerima', 0),
            'formula_7_total' => $row->total_formula_7,
            'formula_12_pool' => (int) data_get($configSnapshot, 'pools.formula_12_pool', 0),
            'formula_12_divider' => (float) data_get($configSnapshot, 'pools.formula_12_divider', 2),
            'formula_12_per_penerima' => (int) data_get($configSnapshot, 'pools.formula_12_per_penerima', 0),
            'formula_12_total' => $row->total_formula_12,
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

    private function divideAmount(int $amount, float $divider): int
    {
        return (int) round($amount / max(0.01, $divider));
    }

    private function grandAllocationPercent(float $jasaPercent, float $formulaPercent, float $divider): float
    {
        return round(($jasaPercent * $formulaPercent / 100) / max(0.01, $divider), 2);
    }

    private function sourceLabel(string $sourceTable): string
    {
        return match ($sourceTable) {
            'detail_pemberian_obat' => 'Detail Pemberian Obat',
            'rawat_jl_pr' => 'Rawat Jalan Paramedis',
            'rawat_inap_pr' => 'Rawat Inap Paramedis',
            'rawat_jl_dr' => 'Rawat Jalan Dokter',
            'rawat_inap_dr' => 'Rawat Inap Dokter',
            'rawat_jl_drpr' => 'Rawat Jalan Dokter & Paramedis',
            'rawat_inap_drpr' => 'Rawat Inap Dokter & Paramedis',
            default => $sourceTable,
        };
    }

    private function sourcePeriodModeFromResult(string $periode, string $sourcePeriode, string $jenisApotek): string
    {
        if ($jenisApotek !== 'bpjs') {
            return PremiSourcePeriod::MODE_CURRENT;
        }

        return $periode === $sourcePeriode
            ? PremiSourcePeriod::MODE_CURRENT
            : PremiSourcePeriod::MODE_PREVIOUS;
    }

    private function typeLabel(string $jenisApotek): string
    {
        return $jenisApotek === 'bpjs' ? 'BPJS Kesehatan' : 'Umum';
    }

    private function normalizeCategory(?string $kategoriPremi): string
    {
        return $kategoriPremi === self::CATEGORY_APOTEKER
            ? self::CATEGORY_APOTEKER
            : self::CATEGORY_APOTEK;
    }

    private function categoryLabel(string $kategoriPremi): string
    {
        return $this->normalizeCategory($kategoriPremi) === self::CATEGORY_APOTEKER
            ? 'Premi Apoteker'
            : 'Premi Apotek';
    }
}
