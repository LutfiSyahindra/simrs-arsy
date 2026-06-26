<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateApotekConfigModel;
use App\Models\dbSimrs\generateApotekModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateApotekRepository
{
    private const CATEGORY_APOTEK = 'apotek';

    private const CATEGORY_APOTEKER = 'apoteker';

    private const SOURCE_TABLES = [
        [
            'table' => 'rawat_jl_pr',
            'source' => 'RAJAL',
            'master' => 'jns_perawatan',
            'provider' => 'pr',
            'label' => 'Rawat Jalan Paramedis',
        ],
        [
            'table' => 'rawat_inap_pr',
            'source' => 'RANAP',
            'master' => 'jns_perawatan_inap',
            'provider' => 'pr',
            'label' => 'Rawat Inap Paramedis',
        ],
        [
            'table' => 'rawat_jl_dr',
            'source' => 'RAJAL',
            'master' => 'jns_perawatan',
            'provider' => 'dr',
            'label' => 'Rawat Jalan Dokter',
        ],
        [
            'table' => 'rawat_inap_dr',
            'source' => 'RANAP',
            'master' => 'jns_perawatan_inap',
            'provider' => 'dr',
            'label' => 'Rawat Inap Dokter',
        ],
        [
            'table' => 'rawat_jl_drpr',
            'source' => 'RAJAL',
            'master' => 'jns_perawatan',
            'provider' => 'drpr',
            'label' => 'Rawat Jalan Dokter & Paramedis',
        ],
        [
            'table' => 'rawat_inap_drpr',
            'source' => 'RANAP',
            'master' => 'jns_perawatan_inap',
            'provider' => 'drpr',
            'label' => 'Rawat Inap Dokter & Paramedis',
        ],
    ];

    public function getResults(?string $periode = null, ?string $jenisApotek = null, string $kategoriPremi = self::CATEGORY_APOTEK): Collection
    {
        return generateApotekModel::query()
            ->with(['jenisTindakan:id,kode,jenis', 'jnsPremi:id,kode,jenis,pembagi', 'lockedBy:id,name', 'generateBy:id,name'])
            ->withCount(['details', 'recipients'])
            ->where('kategori_premi', $this->normalizeCategory($kategoriPremi))
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisApotek, fn ($query) => $query->where('jenis_apotek', $jenisApotek))
            ->orderByDesc('periode')
            ->orderBy('jenis_apotek')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): array
    {
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);
        $rows = $periode
            ? generateApotekModel::query()
                ->where('kategori_premi', $kategoriPremi)
                ->where('periode', $periode)
                ->where('jenis_apotek', $jenisApotek)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_data_sumber' => $rows->sum('jumlah_data_sumber'),
            'jumlah_pasien_sumber' => $rows->sum('jumlah_pasien_sumber'),
            'jumlah_obat_sumber' => $rows->sum('jumlah_obat_sumber'),
            'jumlah_data_mapping' => $rows->sum('jumlah_data_mapping'),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'jumlah_obat' => $rows->sum('jumlah_obat'),
            'jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'jumlah_mapping_premi' => $rows->sum('jumlah_mapping_premi'),
            'total_qty' => $rows->sum('total_qty'),
            'grand_total' => $rows->sum('grand_total'),
            'total_biaya_rawat' => $rows->sum('total_biaya_rawat'),
            'total_mapping_premi' => $rows->sum('total_mapping_premi'),
            'total_final' => $rows->sum('total_final'),
            'total_jasa_farmasi_pool' => $rows->sum('total_jasa_farmasi_pool'),
            'total_formula_31' => $rows->sum('total_formula_31'),
            'total_formula_7' => $rows->sum('total_formula_7'),
            'total_formula_12' => $rows->sum('total_formula_12'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
        ];
    }

    public function getConfig(string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): generateApotekConfigModel
    {
        $this->ensureDefaultConfigs();
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);

        $config = generateApotekConfigModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'jnsPremi:id,kode,jenis,pembagi',
                'mappings.jenisTindakan:id,kode,jenis',
                'pegawai',
            ])
            ->where('kategori_premi', $kategoriPremi)
            ->where('jenis_apotek', $jenisApotek)
            ->firstOrFail();

        if ($kategoriPremi === self::CATEGORY_APOTEK) {
            $this->ensureConfigMappings($config);
        }

        return $config->fresh([
            'jenisTindakan:id,kode,jenis',
            'jnsPremi:id,kode,jenis,pembagi',
            'mappings.jenisTindakan:id,kode,jenis',
            'pegawai',
        ]);
    }

    public function saveConfig(
        string $jenisApotek,
        string $kategoriPremi,
        array $payload,
        array $mappingIds,
        array $recipients
    ): generateApotekConfigModel {
        $this->ensureDefaultConfigs();
        $kategoriPremi = $this->normalizeCategory($kategoriPremi);

        return DB::transaction(function () use ($jenisApotek, $kategoriPremi, $payload, $mappingIds, $recipients) {
            $config = generateApotekConfigModel::query()
                ->where('kategori_premi', $kategoriPremi)
                ->where('jenis_apotek', $jenisApotek)
                ->lockForUpdate()
                ->firstOrFail();

            $mappingIds = collect($mappingIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $payload['kategori_premi'] = $kategoriPremi;
            $payload['jnsTindakan_id'] = $kategoriPremi === self::CATEGORY_APOTEK
                ? $mappingIds->first()
                : null;
            $payload['jnsPremi_id'] = $kategoriPremi === self::CATEGORY_APOTEKER
                ? ($payload['jnsPremi_id'] ?? null)
                : null;

            $config->update($payload);
            $config->mappings()->delete();
            $config->pegawai()->delete();

            $now = now();
            if ($kategoriPremi === self::CATEGORY_APOTEK && $mappingIds->isNotEmpty()) {
                DB::table('generate_apotek_config_mapping')->insert(
                    $mappingIds
                        ->map(fn ($id) => [
                            'config_id' => $config->id,
                            'jnsTindakan_id' => $id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );
            }

            $pegawaiRows = [];
            foreach ($recipients as $role => $items) {
                foreach ($items as $item) {
                    $pegawaiRows[] = [
                        'config_id' => $config->id,
                        'role' => $role,
                        'pegawai_id' => $item['pegawai_id'],
                        'pegawai_name' => $item['pegawai_name'],
                        'pegawai_position' => $item['pegawai_position'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($pegawaiRows)) {
                DB::table('generate_apotek_config_pegawai')->insert($pegawaiRows);
            }

            return $config->fresh([
                'jenisTindakan:id,kode,jenis',
                'jnsPremi:id,kode,jenis,pembagi',
                'mappings.jenisTindakan:id,kode,jenis',
                'pegawai',
            ]);
        });
    }

    public function mappingOptions(?string $keyword = null, string $kategoriPremi = self::CATEGORY_APOTEK): Collection
    {
        if ($this->normalizeCategory($kategoriPremi) === self::CATEGORY_APOTEKER) {
            return $this->premiOptions($keyword);
        }

        return DB::table('master_jenis_tindakan as jt')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->where('mt.sumber_tindakan', '=', 'FARMASI');
            })
            ->select([
                'jt.id',
                'jt.kode',
                'jt.jenis',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('jt.kode', 'like', "%{$keyword}%")
                        ->orWhere('jt.jenis', 'like', "%{$keyword}%");
                });
            })
            ->groupBy('jt.id', 'jt.kode', 'jt.jenis')
            ->orderByDesc('jumlah_mapping')
            ->orderBy('jt.jenis')
            ->limit(50)
            ->get();
    }

    public function premiOptions(?string $keyword = null): Collection
    {
        return DB::table('master_jenis_premi as jp')
            ->leftJoin('mapping_premi as mp', 'mp.jnsPremi_id', '=', 'jp.id')
            ->select([
                'jp.id',
                'jp.kode',
                'jp.jenis',
                'jp.pembagi',
                DB::raw('COUNT(mp.id) as jumlah_mapping'),
            ])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('jp.kode', 'like', "%{$keyword}%")
                        ->orWhere('jp.jenis', 'like', "%{$keyword}%");
                });
            })
            ->groupBy('jp.id', 'jp.kode', 'jp.jenis', 'jp.pembagi')
            ->orderByDesc('jumlah_mapping')
            ->orderBy('jp.jenis')
            ->limit(50)
            ->get();
    }

    public function searchPegawai(?string $keyword = null): Collection
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('stts_aktif', 'AKTIF')
            ->where('nik', '!=', '-')
            ->where('nama', '!=', '-')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('nik', 'like', "%{$keyword}%")
                        ->orWhere('nama', 'like', "%{$keyword}%")
                        ->orWhere('jbtn', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('nama')
            ->limit(50)
            ->get();
    }

    public function findPegawai(string $nik): ?object
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('nik', $nik)
            ->where('stts_aktif', 'AKTIF')
            ->where('nik', '!=', '-')
            ->where('nama', '!=', '-')
            ->first();
    }

    public function getSourceData(string $periode, string $jenisApotek, array $config): array
    {
        $sourcePeriodMode = PremiSourcePeriod::normalizeMode($config['source_period_mode'] ?? null, $jenisApotek);
        $range = PremiSourcePeriod::range($periode, $jenisApotek, $sourcePeriodMode);
        $sourcePeriode = PremiSourcePeriod::resolve($periode, $jenisApotek, $sourcePeriodMode);
        $sourceStart = $range['start'];
        $sourceEnd = $range['end'];
        $sourceEndInclusive = $sourceEnd->copy()->subDay();
        $tarifPerItem = max(0, (int) ($config['tarif_per_item'] ?? 500));
        $mappingIds = $config['jnsTindakan_ids'] ?? [];
        $mappings = $this->selectedMappings($mappingIds);
        $mappingByCode = $mappings->unique('kd_tindakan')->keyBy('kd_tindakan');
        $codes = $mappings->pluck('kd_tindakan')->unique()->values();
        $includeBpjsInUmum = $this->includeBpjsInUmum($jenisApotek, $config);
        $sourceSummary = $this->sourceSummary($jenisApotek, $sourceStart, $sourceEnd, $includeBpjsInUmum);
        $details = collect();

        if ($codes->isNotEmpty()) {
            foreach ($codes->chunk(700) as $codeChunk) {
                $rows = $this->sourceQuery($jenisApotek, $sourceStart, $sourceEnd, $includeBpjsInUmum)
                    ->whereIn('dpo.kode_brng', $codeChunk->values()->all())
                    ->get();

                foreach ($rows as $row) {
                    $mapping = $mappingByCode->get($row->kode_barang);

                    if (! $mapping) {
                        continue;
                    }

                    $details->push($this->detailPayload($row, $mapping, $tarifPerItem));
                }
            }
        }

        $details = $details
            ->unique(fn ($row) => implode('|', [
                $row['no_rawat'],
                $row['tanggal'],
                $row['jam'] ?? '',
                $row['kode_barang'],
                $row['qty'],
                $row['total_obat'],
            ]))
            ->sortBy(fn ($row) => implode('|', [
                $row['tanggal'],
                $row['jam'] ?? '',
                $row['no_rawat'],
                $row['kode_barang'],
            ]))
            ->values();

        return [
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => PremiSourcePeriod::modeLabel($sourcePeriodMode, $jenisApotek),
            'source_tgl_awal' => $sourceStart->toDateString(),
            'source_tgl_akhir' => $sourceEndInclusive->toDateString(),
            'jumlah_data_sumber' => $sourceSummary['jumlah_data_sumber'],
            'jumlah_pasien_sumber' => $sourceSummary['jumlah_pasien_sumber'],
            'jumlah_obat_sumber' => $sourceSummary['jumlah_obat_sumber'],
            'jumlah_data_mapping' => $details->count(),
            'jumlah_pasien' => $details->pluck('no_rawat')->unique()->count(),
            'jumlah_obat' => $details->pluck('kode_barang')->unique()->count(),
            'total_qty' => round((float) $details->sum('qty'), 2),
            'tarif_per_item' => $tarifPerItem,
            'grand_total' => (int) $details->sum('total_premi'),
            'mapping' => $this->mappingSummary($mappingIds, $mappings),
            'details' => $details,
        ];
    }

    public function getApotekerSourceData(string $periode, string $jenisApotek, array $config): array
    {
        $sourcePeriodMode = PremiSourcePeriod::normalizeMode($config['source_period_mode'] ?? null, $jenisApotek);
        $range = PremiSourcePeriod::range($periode, $jenisApotek, $sourcePeriodMode);
        $sourcePeriode = PremiSourcePeriod::resolve($periode, $jenisApotek, $sourcePeriodMode);
        $sourceStart = $range['start'];
        $sourceEnd = $range['end'];
        $sourceEndInclusive = $sourceEnd->copy()->subDay();
        $jnsPremiId = (int) ($config['jnsPremi_id'] ?? 0);
        $includeBpjsInUmum = $this->includeBpjsInUmum($jenisApotek, $config);
        $mappings = $this->selectedPremiMappings($jnsPremiId, $jenisApotek);
        $sourceSummary = $this->tindakanSourceSummary($jenisApotek, $sourceStart, $sourceEnd, $includeBpjsInUmum);
        $details = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            $sourceMappings = $mappings
                ->where('sumber_tindakan', $definition['source'])
                ->values();
            $codes = $sourceMappings
                ->pluck('kd_tindakan')
                ->unique()
                ->values();

            if ($codes->isEmpty()) {
                continue;
            }

            $mappingByCode = $sourceMappings->groupBy('kd_tindakan');
            foreach ($codes->chunk(700) as $codeChunk) {
                $rows = $this->tindakanSourceQuery(
                    $definition,
                    $jenisApotek,
                    $sourceStart,
                    $sourceEnd,
                    $codeChunk->values(),
                    $includeBpjsInUmum
                )->get();

                foreach ($rows as $row) {
                    foreach ($mappingByCode->get($row->kd_tindakan, collect()) as $mapping) {
                        $details->push($this->apotekerDetailPayload($row, $mapping));
                    }
                }
            }
        }

        $details = $details
            ->unique(fn ($row) => implode('|', [
                $row['mapping_premi_id'],
                $row['source_table'],
                $row['no_rawat'],
                $row['tanggal'],
                $row['jam'] ?? '',
                $row['kd_tindakan'],
                $row['kd_dokter'] ?? '',
                $row['nip'] ?? '',
                $row['biaya_rawat'],
            ]))
            ->sortBy(fn ($row) => implode('|', [
                $row['tanggal'],
                $row['jam'] ?? '',
                $row['no_rawat'],
                $row['source_table'],
                $row['kd_tindakan'],
            ]))
            ->values();

        $calculationDetails = $this->apotekerCalculationDetails($details, $mappings, $jenisApotek);
        $totalBiayaRawat = (int) round($details->sum('biaya_rawat'));
        $totalMappingPremi = (int) round($calculationDetails->sum('hasil_mapping'));
        $pembagi = max(1, (int) ($mappings->first()?->pembagi ?? $config['pembagi'] ?? 1));

        return [
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => PremiSourcePeriod::modeLabel($sourcePeriodMode, $jenisApotek),
            'source_tgl_awal' => $sourceStart->toDateString(),
            'source_tgl_akhir' => $sourceEndInclusive->toDateString(),
            'jumlah_data_sumber' => $sourceSummary['jumlah_data_sumber'],
            'jumlah_pasien_sumber' => $sourceSummary['jumlah_pasien_sumber'],
            'jumlah_obat_sumber' => $sourceSummary['jumlah_tindakan_sumber'],
            'jumlah_data_mapping' => $details->count(),
            'jumlah_pasien' => $details->pluck('no_rawat')->unique()->count(),
            'jumlah_obat' => $details->pluck('kd_tindakan')->unique()->count(),
            'jumlah_tindakan' => $details->count(),
            'jumlah_mapping_premi' => $calculationDetails->count(),
            'total_qty' => (float) $details->count(),
            'tarif_per_item' => 0,
            'grand_total' => $totalBiayaRawat,
            'total_biaya_rawat' => $totalBiayaRawat,
            'total_mapping_premi' => $totalMappingPremi,
            'pembagi' => $pembagi,
            'total_final' => (int) round($totalMappingPremi / $pembagi),
            'mapping' => $this->premiMappingSummary($jnsPremiId, $mappings, $calculationDetails),
            'calculation_details' => $calculationDetails,
            'details' => $details,
        ];
    }

    public function premiRecipients(int $jnsPremiId): Collection
    {
        if ($jnsPremiId < 1) {
            return collect();
        }

        return DB::table('mapping_premi_pegawai as mpp')
            ->leftJoin('gaji_pokok as pg', 'pg.nik', '=', 'mpp.nik')
            ->select([
                'mpp.nik as pegawai_id',
                DB::raw('COALESCE(pg.nama, mpp.nik) as pegawai_name'),
                'pg.jbtn as pegawai_position',
            ])
            ->where('mpp.jnsPremi_id', $jnsPremiId)
            ->orderBy('pegawai_name')
            ->get();
    }

    public function findExistingForUpdate(string $periode, string $jenisApotek, string $kategoriPremi = self::CATEGORY_APOTEK): ?generateApotekModel
    {
        return generateApotekModel::query()
            ->where('kategori_premi', $this->normalizeCategory($kategoriPremi))
            ->where('periode', $periode)
            ->where('jenis_apotek', $jenisApotek)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisApotek, array $calculation): generateApotekModel
    {
        if (($calculation['kategori_premi'] ?? self::CATEGORY_APOTEK) === self::CATEGORY_APOTEKER) {
            return $this->saveApotekerResult($periode, $jenisApotek, $calculation);
        }

        $source = $calculation['source'];
        $mapping = $source['mapping'];
        $primaryMapping = $mapping['items'][0] ?? null;
        $pools = $calculation['pools'];

        $result = generateApotekModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_apotek' => $jenisApotek,
                'kategori_premi' => self::CATEGORY_APOTEK,
            ],
            [
                'kategori_premi' => self::CATEGORY_APOTEK,
                'source_periode' => $source['source_periode'],
                'source_period_mode' => $source['source_period_mode'],
                'source_tgl_awal' => $source['source_tgl_awal'],
                'source_tgl_akhir' => $source['source_tgl_akhir'],
                'jnsPremi_id' => null,
                'kode_premi' => null,
                'nama_premi' => null,
                'pembagi' => 1,
                'jnsTindakan_id' => $primaryMapping['id'] ?? null,
                'kode_jenis_tindakan' => $primaryMapping['kode'] ?? null,
                'nama_jenis_tindakan' => $mapping['label'] ?? ($primaryMapping['jenis'] ?? null),
                'jumlah_data_sumber' => $source['jumlah_data_sumber'],
                'jumlah_pasien_sumber' => $source['jumlah_pasien_sumber'],
                'jumlah_obat_sumber' => $source['jumlah_obat_sumber'],
                'jumlah_data_mapping' => $source['jumlah_data_mapping'],
                'jumlah_pasien' => $source['jumlah_pasien'],
                'jumlah_obat' => $source['jumlah_obat'],
                'jumlah_tindakan' => 0,
                'jumlah_mapping_premi' => 0,
                'total_qty' => $source['total_qty'],
                'tarif_per_item' => $source['tarif_per_item'],
                'grand_total' => $source['grand_total'],
                'total_biaya_rawat' => 0,
                'total_mapping_premi' => 0,
                'total_final' => 0,
                'total_jasa_farmasi_pool' => $pools['jasa_farmasi_pool'],
                'total_formula_31' => $pools['formula_31_total'],
                'total_formula_7' => $pools['formula_7_total'],
                'total_formula_12' => $pools['formula_12_total'],
                'total_premi_bersama' => $pools['premi_bersama'],
                'total_dibagikan' => $calculation['total_dibagikan'],
                'config_snapshot' => $calculation['config_snapshot'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();
        $result->recipients()->delete();
        $now = now();

        $source['details']
            ->map(fn (array $detail) => [
                ...$detail,
                'generate_apotek_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(500)
            ->each(fn (Collection $chunk) => DB::table('generate_apotek_detail')->insert($chunk->all()));

        collect($calculation['recipients'])
            ->map(fn (array $recipient) => [
                ...$recipient,
                'generate_apotek_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(200)
            ->each(fn (Collection $chunk) => DB::table('generate_apotek_recipient')->insert($chunk->all()));

        return $result->fresh([
            'details',
            'recipients',
            'jenisTindakan:id,kode,jenis',
            'jnsPremi:id,kode,jenis,pembagi',
            'lockedBy:id,name',
            'generateBy:id,name',
        ]);
    }

    private function saveApotekerResult(string $periode, string $jenisApotek, array $calculation): generateApotekModel
    {
        $source = $calculation['source'];
        $mapping = $source['mapping'];
        $primaryMapping = $mapping['primary'] ?? null;

        $result = generateApotekModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_apotek' => $jenisApotek,
                'kategori_premi' => self::CATEGORY_APOTEKER,
            ],
            [
                'kategori_premi' => self::CATEGORY_APOTEKER,
                'source_periode' => $source['source_periode'],
                'source_period_mode' => $source['source_period_mode'],
                'source_tgl_awal' => $source['source_tgl_awal'],
                'source_tgl_akhir' => $source['source_tgl_akhir'],
                'jnsTindakan_id' => $primaryMapping['jnsTindakan_id'] ?? null,
                'jnsPremi_id' => $mapping['jnsPremi_id'] ?? null,
                'kode_premi' => $mapping['kode'] ?? null,
                'nama_premi' => $mapping['jenis'] ?? null,
                'pembagi' => $source['pembagi'],
                'kode_jenis_tindakan' => $primaryMapping['kode_jenis_tindakan'] ?? null,
                'nama_jenis_tindakan' => $mapping['label'] ?? ($primaryMapping['nama_jenis_tindakan'] ?? null),
                'jumlah_data_sumber' => $source['jumlah_data_sumber'],
                'jumlah_pasien_sumber' => $source['jumlah_pasien_sumber'],
                'jumlah_obat_sumber' => $source['jumlah_obat_sumber'],
                'jumlah_data_mapping' => $source['jumlah_data_mapping'],
                'jumlah_pasien' => $source['jumlah_pasien'],
                'jumlah_obat' => $source['jumlah_obat'],
                'jumlah_tindakan' => $source['jumlah_tindakan'],
                'jumlah_mapping_premi' => $source['jumlah_mapping_premi'],
                'total_qty' => $source['total_qty'],
                'tarif_per_item' => 0,
                'grand_total' => $source['grand_total'],
                'total_biaya_rawat' => $source['total_biaya_rawat'],
                'total_mapping_premi' => $source['total_mapping_premi'],
                'total_final' => $source['total_final'],
                'total_jasa_farmasi_pool' => $source['total_mapping_premi'],
                'total_formula_31' => $source['total_final'],
                'total_formula_7' => 0,
                'total_formula_12' => 0,
                'total_premi_bersama' => 0,
                'total_dibagikan' => $source['total_final'],
                'config_snapshot' => $calculation['config_snapshot'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();
        $result->recipients()->delete();
        $now = now();

        $source['details']
            ->map(fn (array $detail) => [
                ...$detail,
                'generate_apotek_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(500)
            ->each(fn (Collection $chunk) => DB::table('generate_apotek_detail')->insert($chunk->all()));

        collect($calculation['recipients'])
            ->map(fn (array $recipient) => [
                ...$recipient,
                'generate_apotek_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(200)
            ->each(fn (Collection $chunk) => DB::table('generate_apotek_recipient')->insert($chunk->all()));

        return $result->fresh([
            'details',
            'recipients',
            'jenisTindakan:id,kode,jenis',
            'jnsPremi:id,kode,jenis,pembagi',
            'lockedBy:id,name',
            'generateBy:id,name',
        ]);
    }

    public function findForUpdate(int $id): ?generateApotekModel
    {
        return generateApotekModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateApotekModel
    {
        return generateApotekModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'jnsPremi:id,kode,jenis,pembagi',
                'lockedBy:id,name',
                'generateBy:id,name',
                'details' => fn ($query) => $query
                    ->orderBy('tanggal')
                    ->orderBy('jam')
                    ->orderBy('no_rawat')
                    ->orderBy('source_table')
                    ->orderBy('kode_barang')
                    ->orderBy('kd_tindakan'),
                'recipients' => fn ($query) => $query
                    ->orderByRaw("FIELD(role, 'penerima_31', 'penerima_7', 'penerima_12', 'apoteker')")
                    ->orderBy('pegawai_name'),
            ])
            ->find($id);
    }

    public function updateLock(
        generateApotekModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateApotekModel {
        DB::table('generate_apotek')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateApotekModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateApotekModel $result): void
    {
        $result->delete();
    }

    private function sourceSummary(string $jenisApotek, $sourceStart, $sourceEnd, bool $includeBpjsInUmum = false): array
    {
        $row = $this->sourceBaseQuery($jenisApotek, $sourceStart, $sourceEnd, $includeBpjsInUmum)
            ->selectRaw('COUNT(*) as jumlah_data_sumber')
            ->selectRaw('COUNT(DISTINCT dpo.no_rawat) as jumlah_pasien_sumber')
            ->selectRaw('COUNT(DISTINCT dpo.kode_brng) as jumlah_obat_sumber')
            ->first();

        return [
            'jumlah_data_sumber' => (int) ($row->jumlah_data_sumber ?? 0),
            'jumlah_pasien_sumber' => (int) ($row->jumlah_pasien_sumber ?? 0),
            'jumlah_obat_sumber' => (int) ($row->jumlah_obat_sumber ?? 0),
        ];
    }

    private function sourceQuery(string $jenisApotek, $sourceStart, $sourceEnd, bool $includeBpjsInUmum = false)
    {
        return $this->sourceBaseQuery($jenisApotek, $sourceStart, $sourceEnd, $includeBpjsInUmum)
            ->select([
                DB::raw("'detail_pemberian_obat' as source_table"),
                DB::raw("'FARMASI' as sumber_tindakan"),
                'dpo.no_rawat',
                'rp.no_rkm_medis',
                'ps.nm_pasien',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
                'dpo.tgl_perawatan as tanggal',
                'dpo.jam',
                'dpo.kode_brng as kode_barang',
                DB::raw('COALESCE(db.nama_brng, dpo.kode_brng) as nama_barang'),
                'dpo.jml as qty',
                'dpo.biaya_obat as harga_obat',
                'dpo.total as total_obat',
                'dpo.status',
            ]);
    }

    private function sourceBaseQuery(string $jenisApotek, $sourceStart, $sourceEnd, bool $includeBpjsInUmum = false)
    {
        return DB::connection('mysql_khanza')
            ->table('detail_pemberian_obat as dpo')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'dpo.no_rawat')
            ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->leftJoin('databarang as db', 'db.kode_brng', '=', 'dpo.kode_brng')
            ->where('dpo.tgl_perawatan', '>=', $sourceStart->toDateString())
            ->where('dpo.tgl_perawatan', '<', $sourceEnd->toDateString())
            ->when(
                $jenisApotek === 'bpjs',
                fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                fn ($query) => $includeBpjsInUmum
                    ? $query->where('rp.kd_pj', '!=', '-')
                    : $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
            );
    }

    private function tindakanSourceSummary(string $jenisApotek, Carbon $start, Carbon $end, bool $includeBpjsInUmum = false): array
    {
        $jumlahData = 0;
        $patients = collect();
        $actions = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            $query = DB::connection('mysql_khanza')
                ->table($definition['table'].' as r')
                ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'r.no_rawat')
                ->where('r.tgl_perawatan', '>=', $start->toDateString())
                ->where('r.tgl_perawatan', '<', $end->toDateString())
                ->where('r.biaya_rawat', '>', 0)
                ->when(
                    $jenisApotek === 'bpjs',
                    fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                    fn ($query) => $includeBpjsInUmum
                        ? $query->where('rp.kd_pj', '!=', '-')
                        : $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
                );

            $jumlahData += (clone $query)->count();
            $patients = $patients->merge(
                (clone $query)
                    ->distinct()
                    ->pluck('r.no_rawat')
            );
            $actions = $actions->merge(
                (clone $query)
                    ->distinct()
                    ->pluck('r.kd_jenis_prw')
            );
        }

        return [
            'jumlah_data_sumber' => $jumlahData,
            'jumlah_pasien_sumber' => $patients->unique()->count(),
            'jumlah_tindakan_sumber' => $actions->unique()->count(),
        ];
    }

    private function tindakanSourceQuery(
        array $definition,
        string $jenisApotek,
        Carbon $start,
        Carbon $end,
        Collection $codes,
        bool $includeBpjsInUmum = false
    ) {
        $hasDoctor = in_array($definition['provider'], ['dr', 'drpr'], true);
        $hasPetugas = in_array($definition['provider'], ['pr', 'drpr'], true);

        $query = DB::connection('mysql_khanza')
            ->table($definition['table'].' as r')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'r.no_rawat')
            ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->leftJoin($definition['master'].' as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
            ->select([
                DB::raw("'{$definition['table']}' as source_table"),
                DB::raw("'{$definition['source']}' as sumber_tindakan"),
                DB::raw("'{$definition['label']}' as sumber_label"),
                'r.no_rawat',
                'rp.no_rkm_medis',
                'ps.nm_pasien',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
                'r.tgl_perawatan as tanggal',
                'r.jam_rawat as jam',
                'r.kd_jenis_prw as kd_tindakan',
                DB::raw('COALESCE(jp.nm_perawatan, r.kd_jenis_prw) as nm_tindakan'),
                'r.biaya_rawat',
            ])
            ->where('r.tgl_perawatan', '>=', $start->toDateString())
            ->where('r.tgl_perawatan', '<', $end->toDateString())
            ->where('r.biaya_rawat', '>', 0)
            ->whereIn('r.kd_jenis_prw', $codes->values()->all())
            ->when(
                $jenisApotek === 'bpjs',
                fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                fn ($query) => $includeBpjsInUmum
                    ? $query->where('rp.kd_pj', '!=', '-')
                    : $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
            );

        if ($hasDoctor) {
            $query->leftJoin('dokter as d', 'd.kd_dokter', '=', 'r.kd_dokter')
                ->selectRaw('r.kd_dokter as kd_dokter')
                ->selectRaw('d.nm_dokter as nm_dokter');
        } else {
            $query->selectRaw('null as kd_dokter')
                ->selectRaw('null as nm_dokter');
        }

        if ($hasPetugas) {
            $query->leftJoin('petugas as pt', 'pt.nip', '=', 'r.nip')
                ->selectRaw('r.nip as nip')
                ->selectRaw('pt.nama as nama_petugas');
        } else {
            $query->selectRaw('null as nip')
                ->selectRaw('null as nama_petugas');
        }

        return $query;
    }

    private function selectedMappings(array $jnsTindakanIds): Collection
    {
        $jnsTindakanIds = collect($jnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($jnsTindakanIds->isEmpty()) {
            return collect();
        }

        return DB::table('mapping_tindakan as mt')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->select([
                'mt.id',
                'mt.jnsTindakan_id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->where('mt.sumber_tindakan', 'FARMASI')
            ->whereIn('mt.jnsTindakan_id', $jnsTindakanIds->all())
            ->get();
    }

    private function selectedPremiMappings(int $jnsPremiId, string $jenisApotek): Collection
    {
        if ($jnsPremiId < 1) {
            return collect();
        }

        $valueColumn = $jenisApotek === 'bpjs'
            ? 'mp.nilai_bpjs'
            : 'mp.nilai_umum';

        return DB::table('mapping_tindakan as mt')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->join('mapping_premi as mp', 'mp.jnsTindakan_id', '=', 'mt.jnsTindakan_id')
            ->join('master_jenis_premi as jp', 'jp.id', '=', 'mp.jnsPremi_id')
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.jnsTindakan_id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mp.id as mapping_premi_id',
                'mp.jnsPremi_id',
                'mp.jenis as jenis_mapping',
                'mp.nilai_umum',
                'mp.nilai_bpjs',
                'jp.pembagi',
                DB::raw("{$valueColumn} as nilai_mapping"),
                'jp.kode as kode_premi',
                'jp.jenis as nama_premi',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->where('mp.jnsPremi_id', $jnsPremiId)
            ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP'])
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.kd_tindakan')
            ->get();
    }

    private function detailPayload(object $row, object $mapping, int $tarifPerItem): array
    {
        $qty = round((float) $row->qty, 2);

        return [
            'mapping_tindakan_id' => $mapping->id,
            'jnsTindakan_id' => $mapping->jnsTindakan_id,
            'source_table' => $row->source_table,
            'sumber_tindakan' => $row->sumber_tindakan,
            'no_rawat' => $row->no_rawat,
            'no_rkm_medis' => $row->no_rkm_medis,
            'nm_pasien' => $row->nm_pasien,
            'kd_pj' => $row->kd_pj,
            'nama_penjamin' => $row->nama_penjamin,
            'tanggal' => $row->tanggal,
            'jam' => $this->cleanTime($row->jam),
            'kode_barang' => $row->kode_barang,
            'nama_barang' => $mapping->nm_tindakan ?: $row->nama_barang,
            'qty' => $qty,
            'harga_obat' => (int) round((float) $row->harga_obat),
            'total_obat' => (int) round((float) $row->total_obat),
            'nominal_premi' => $tarifPerItem,
            'total_premi' => (int) round($qty * $tarifPerItem),
            'status' => $row->status,
        ];
    }

    private function apotekerDetailPayload(object $row, object $mapping): array
    {
        $biayaRawat = (int) round((float) $row->biaya_rawat);
        $nilaiMapping = (float) $mapping->nilai_mapping;
        $hasilMapping = $mapping->jenis_mapping === 'persen'
            ? (int) round($biayaRawat * $nilaiMapping / 100)
            : (int) round($nilaiMapping);
        $namaTindakan = $mapping->nm_tindakan ?: $row->nm_tindakan;

        return [
            'mapping_tindakan_id' => $mapping->mapping_tindakan_id,
            'mapping_premi_id' => $mapping->mapping_premi_id,
            'jnsTindakan_id' => $mapping->jnsTindakan_id,
            'source_table' => $row->source_table,
            'sumber_tindakan' => $row->sumber_tindakan,
            'no_rawat' => $row->no_rawat,
            'no_rkm_medis' => $row->no_rkm_medis,
            'nm_pasien' => $row->nm_pasien,
            'kd_pj' => $row->kd_pj,
            'nama_penjamin' => $row->nama_penjamin,
            'tanggal' => $row->tanggal,
            'jam' => $this->cleanTime($row->jam),
            'kode_barang' => $row->kd_tindakan,
            'nama_barang' => $namaTindakan,
            'qty' => 1,
            'harga_obat' => $biayaRawat,
            'total_obat' => $biayaRawat,
            'nominal_premi' => $hasilMapping,
            'total_premi' => $hasilMapping,
            'status' => 'TINDAKAN',
            'kd_tindakan' => $row->kd_tindakan,
            'nm_tindakan' => $namaTindakan,
            'kd_dokter' => $row->kd_dokter,
            'nm_dokter' => $row->nm_dokter,
            'nip' => $row->nip,
            'nama_petugas' => $row->nama_petugas,
            'biaya_rawat' => $biayaRawat,
            'jenis_mapping' => $mapping->jenis_mapping,
            'nilai_mapping' => $nilaiMapping,
            'hasil_mapping' => $hasilMapping,
        ];
    }

    private function apotekerCalculationDetails(Collection $details, Collection $mappings, string $jenisApotek): Collection
    {
        return $details
            ->groupBy('mapping_premi_id')
            ->map(function (Collection $items, $mappingPremiId) use ($mappings, $jenisApotek) {
                $mapping = $mappings->firstWhere('mapping_premi_id', (int) $mappingPremiId);
                $totalBiaya = (int) round($items->sum('biaya_rawat'));
                $jumlahData = $items->count();
                $nilaiMapping = (float) ($mapping?->nilai_mapping ?? 0);
                $jenisMapping = $mapping?->jenis_mapping ?? 'persen';
                $dasarHitung = $jenisMapping === 'persen'
                    ? $totalBiaya
                    : $jumlahData;
                $hasil = $jenisMapping === 'persen'
                    ? (int) round($totalBiaya * $nilaiMapping / 100)
                    : (int) round($jumlahData * $nilaiMapping);

                return [
                    'mapping_premi_id' => (int) $mappingPremiId,
                    'jnsPremi_id' => (int) ($mapping?->jnsPremi_id ?? 0),
                    'jnsTindakan_id' => (int) ($mapping?->jnsTindakan_id ?? 0),
                    'kode_premi' => $mapping?->kode_premi,
                    'nama_premi' => $mapping?->nama_premi,
                    'kode_jenis_tindakan' => $mapping?->kode_jenis_tindakan,
                    'nama_jenis_tindakan' => $mapping?->nama_jenis_tindakan,
                    'jenis_mapping' => $jenisMapping,
                    'nilai_mapping' => $nilaiMapping,
                    'nilai_mapping_label' => $jenisMapping === 'persen'
                        ? $nilaiMapping.'%'
                        : 'Rp '.number_format($nilaiMapping, 0, ',', '.'),
                    'jenis_apotek' => $jenisApotek,
                    'jumlah_data' => $jumlahData,
                    'total_biaya_rawat' => $totalBiaya,
                    'dasar_hitung' => $dasarHitung,
                    'hasil_mapping' => $hasil,
                ];
            })
            ->sortBy('nama_jenis_tindakan')
            ->values();
    }

    private function mappingSummary(array $jnsTindakanIds, Collection $mappings): ?array
    {
        $jnsTindakanIds = collect($jnsTindakanIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($jnsTindakanIds->isEmpty()) {
            return null;
        }

        $jenisRows = DB::table('master_jenis_tindakan')
            ->select('id', 'kode', 'jenis')
            ->whereIn('id', $jnsTindakanIds->all())
            ->orderBy('jenis')
            ->get();

        if ($jenisRows->isEmpty()) {
            return null;
        }

        $items = $jenisRows->map(fn ($jenis) => [
            'id' => (int) $jenis->id,
            'kode' => $jenis->kode,
            'jenis' => $jenis->jenis,
            'label' => trim($jenis->kode.' - '.$jenis->jenis),
            'jumlah_mapping' => $mappings
                ->where('jnsTindakan_id', (int) $jenis->id)
                ->count(),
        ])->values();
        $first = $items->first();
        $label = $items->count() > 1
            ? $first['label'].' + '.($items->count() - 1).' mapping'
            : $first['label'];

        return [
            'id' => $first['id'],
            'ids' => $items->pluck('id')->all(),
            'kode' => $first['kode'],
            'jenis' => $first['jenis'],
            'jumlah_mapping' => $mappings->count(),
            'jumlah_jenis_tindakan' => $items->count(),
            'label' => $label,
            'items' => $items->all(),
        ];
    }

    private function premiMappingSummary(int $jnsPremiId, Collection $mappings, Collection $calculationDetails): ?array
    {
        if ($jnsPremiId < 1) {
            return null;
        }

        $premi = DB::table('master_jenis_premi')
            ->select('id', 'kode', 'jenis', 'pembagi')
            ->where('id', $jnsPremiId)
            ->first();

        if (! $premi) {
            return null;
        }

        $items = $calculationDetails
            ->map(fn ($detail) => [
                'mapping_premi_id' => $detail['mapping_premi_id'],
                'jnsTindakan_id' => $detail['jnsTindakan_id'],
                'kode' => $detail['kode_jenis_tindakan'],
                'jenis' => $detail['nama_jenis_tindakan'],
                'label' => trim(($detail['kode_jenis_tindakan'] ? $detail['kode_jenis_tindakan'].' - ' : '').$detail['nama_jenis_tindakan']),
                'jenis_mapping' => $detail['jenis_mapping'],
                'nilai_mapping' => $detail['nilai_mapping'],
                'jumlah_data' => $detail['jumlah_data'],
                'total_biaya_rawat' => $detail['total_biaya_rawat'],
                'hasil_mapping' => $detail['hasil_mapping'],
            ])
            ->values();
        $first = $items->first();
        $label = trim(($premi->kode ? $premi->kode.' - ' : '').$premi->jenis);

        return [
            'id' => (int) $premi->id,
            'jnsPremi_id' => (int) $premi->id,
            'kode' => $premi->kode,
            'jenis' => $premi->jenis,
            'pembagi' => max(1, (int) ($premi->pembagi ?? 1)),
            'jumlah_mapping' => $mappings->count(),
            'jumlah_mapping_premi' => $calculationDetails->count(),
            'jumlah_jenis_tindakan' => $mappings->pluck('jnsTindakan_id')->unique()->count(),
            'label' => $label,
            'primary' => $first,
            'items' => $items->all(),
            'calculation_details' => $calculationDetails->all(),
        ];
    }

    private function cleanTime(?string $time): ?string
    {
        $time = trim((string) $time);

        return $time === '' ? null : $time;
    }

    private function ensureDefaultConfigs(): void
    {
        foreach ([self::CATEGORY_APOTEK, self::CATEGORY_APOTEKER] as $kategori) {
            foreach (['umum', 'bpjs'] as $jenis) {
                $defaultSourceMode = $jenis === 'bpjs'
                    ? PremiSourcePeriod::MODE_PREVIOUS
                    : PremiSourcePeriod::MODE_CURRENT;
                $config = generateApotekConfigModel::query()->firstOrCreate(
                    [
                        'kategori_premi' => $kategori,
                        'jenis_apotek' => $jenis,
                    ],
                    [
                        'jnsTindakan_id' => null,
                        'jnsPremi_id' => null,
                        'tarif_per_item' => 500,
                        'source_period_mode' => $defaultSourceMode,
                        'include_bpjs_in_umum' => false,
                        'jasa_farmasi_percent' => 50,
                        'formula_31_percent' => 31,
                        'formula_31_divider' => 2.5,
                        'formula_7_percent' => 7,
                        'formula_7_divider' => 1,
                        'formula_12_percent' => 12,
                        'formula_12_divider' => 2,
                        'premi_bersama_percent' => 30,
                    ]
                );

                if (! in_array($config->source_period_mode, [PremiSourcePeriod::MODE_PREVIOUS, PremiSourcePeriod::MODE_CURRENT], true)) {
                    $config->update(['source_period_mode' => $defaultSourceMode]);
                }
            }
        }
    }

    private function ensureConfigMappings(generateApotekConfigModel $config): void
    {
        if (! $config->jnsTindakan_id || $config->mappings->isNotEmpty()) {
            return;
        }

        DB::table('generate_apotek_config_mapping')->insert([
            'config_id' => $config->id,
            'jnsTindakan_id' => $config->jnsTindakan_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function includeBpjsInUmum(string $jenisApotek, array $config): bool
    {
        return $jenisApotek === 'umum' && (bool) ($config['include_bpjs_in_umum'] ?? false);
    }

    private function normalizeCategory(?string $kategoriPremi): string
    {
        return $kategoriPremi === self::CATEGORY_APOTEKER
            ? self::CATEGORY_APOTEKER
            : self::CATEGORY_APOTEK;
    }
}
