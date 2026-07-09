<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generatePremiDokterModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class generatePremiDokterRepository
{
    public const TYPE_VISITE = 'visite';

    public const TYPE_KEBERSAMAAN = 'kebersamaan';

    public const TYPE_OPERASI = 'jasa_operasi';

    public const TYPE_RAWAT_JALAN = 'jasa_rawat_jalan';

    public const TYPE_POLI = 'jasa_poli';

    public const TYPE_ECG = 'jasa_ecg';

    public const TYPE_KONSUL_WA = 'konsul_wa';

    public const TYPE_IGD = 'jasa_igd';

    public const TYPE_KEHADIRAN = 'kehadiran';

    public const ECG_DISTRIBUTION_SPLIT_EVENLY = 'split_evenly';

    public const ECG_DISTRIBUTION_FULL_AMOUNT = 'full_amount';

    public const SERVICE_KEBERSAMAAN = 'all';

    public const SERVICE_MANUAL = 'manual';

    public const SERVICE_ALL = 'all';

    public const CATEGORY_UMUM = 'umum';

    public const CATEGORY_SPESIALIS_65 = 'spesialis_65';

    public const CATEGORY_SPESIALIS_80 = 'spesialis_80';

    public const CATEGORY_KEBERSAMAAN = 'kebersamaan';

    public const CATEGORY_OPERASI = 'jasa_operasi';

    public const CATEGORY_RAWAT_JALAN = 'jasa_rawat_jalan';

    public const CATEGORY_POLI = 'jasa_poli';

    public const CATEGORY_ECG = 'jasa_ecg';

    public const CATEGORY_KONSUL_WA = 'konsul_wa';

    public const CATEGORY_IGD = 'jasa_igd';

    public const CATEGORY_KEHADIRAN = 'kehadiran';

    public const CATEGORY_RAWAT_JALAN_SPECIAL_45000 = 'rawat_jalan_khusus_45000';

    public const CATEGORY_RAWAT_JALAN_SPECIAL_72000 = 'rawat_jalan_khusus_72000';

    public const MULTIPLIER_NOMINAL = 'nominal';

    public const MULTIPLIER_PERCENT = 'percent';

    public const SOURCE_PERIOD_CURRENT = 'current';

    public const SOURCE_PERIOD_PREVIOUS = 'previous';

    private const VISITE_CATEGORIES = [
        self::CATEGORY_UMUM,
        self::CATEGORY_SPESIALIS_65,
        self::CATEGORY_SPESIALIS_80,
    ];

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

    public function getResults(
        ?string $periode = null,
        ?string $jenisPelayanan = null,
        ?string $jenisPremiDokter = self::TYPE_VISITE
    ): Collection {
        return generatePremiDokterModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($jenisPremiDokter, fn ($query) => $query->where('jenis_premi_dokter', $jenisPremiDokter))
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisPelayanan, fn ($query) => $query->where('jenis_pelayanan', $jenisPelayanan))
            ->orderByDesc('periode')
            ->orderBy('jenis_premi_dokter')
            ->orderBy('jenis_pelayanan')
            ->get();
    }

    public function getConfig(): object
    {
        $config = DB::table('generate_premi_dokter_configs')->first();

        if ($config) {
            return $config;
        }

        $payload = [
            'visite_umum_percent' => 50,
            'visite_bpjs_percent' => 50,
            'visite_bpjs_nominal' => 0,
            'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
            'kebersamaan_umum_percent' => 30,
            'kebersamaan_bpjs_nominal' => 40000,
            'kebersamaan_bpjs_percent' => 30,
            'kebersamaan_divider' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_nominal')) {
            $payload['ecg_nominal'] = 5000;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_divider')) {
            $payload['ecg_divider'] = 3;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_distribution_mode')) {
            $payload['ecg_distribution_mode'] = self::ECG_DISTRIBUTION_SPLIT_EVENLY;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'poli_percent')) {
            $payload['poli_percent'] = 30;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'poli_distribution_mode')) {
            $payload['poli_distribution_mode'] = self::ECG_DISTRIBUTION_SPLIT_EVENLY;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'konsul_wa_nominal')) {
            $payload['konsul_wa_nominal'] = 0;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_only_umum')) {
            $payload['kebersamaan_only_umum'] = false;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'igd_nominal_per_pasien')) {
            $payload['igd_nominal_per_pasien'] = 30000;
        }

        if (Schema::hasColumn('generate_premi_dokter_configs', 'kehadiran_nominal_per_hadir')) {
            $payload['kehadiran_nominal_per_hadir'] = 250000;
        }

        $id = DB::table('generate_premi_dokter_configs')->insertGetId($payload);

        if (Schema::hasTable('generate_premi_dokter_poli_filter_source')) {
            $now = now();
            $sourceRows = $this->poliSourceTableOptions()
                ->map(fn ($source) => [
                    'config_id' => $id,
                    'source_table' => $source->source_table,
                    'source_label' => $source->source_label,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($sourceRows->isNotEmpty()) {
                DB::table('generate_premi_dokter_poli_filter_source')->insert($sourceRows->all());
            }
        }

        return DB::table('generate_premi_dokter_configs')->where('id', $id)->first();
    }

    public function poliSourceTableOptions(): Collection
    {
        return collect(self::SOURCE_TABLES)
            ->map(fn (array $row) => (object) [
                'id' => $row['table'],
                'source_table' => $row['table'],
                'source_label' => $row['label'],
                'sumber_tindakan' => $row['source'],
                'provider' => $row['provider'],
            ])
            ->values();
    }

    public function saveConfig(
        float $visiteUmumPercent,
        float $visiteBpjsPercent,
        int $visiteBpjsNominal,
        string $sourcePeriodMode,
        float $kebersamaanUmumPercent,
        int $kebersamaanBpjsNominal,
        float $kebersamaanBpjsPercent,
        int $kebersamaanDivider,
        bool $kebersamaanOnlyUmum,
        int $ecgNominal,
        int $ecgDivider,
        string $ecgDistributionMode,
        float $poliPercent,
        string $poliDistributionMode,
        int $konsulWaNominal,
        int $igdNominalPerPasien,
        int $kehadiranNominalPerHadir,
        array $jnsTindakanIds,
        array $doctorRows,
        array $ecgJnsTindakanIds = [],
        array $poliJnsTindakanIds = [],
        array $poliFilterDoctorRows = [],
        array $poliFilterSourceRows = [],
        array $poliFilterJnsTindakanIds = [],
        array $konsulWaJnsTindakanIds = [],
        array $rawatJalanMappingRows = [],
        array $rawatJalanSpecialDoctorRows = []
    ): object {
        return DB::transaction(function () use (
            $visiteUmumPercent,
            $visiteBpjsPercent,
            $visiteBpjsNominal,
            $sourcePeriodMode,
            $kebersamaanUmumPercent,
            $kebersamaanBpjsNominal,
            $kebersamaanBpjsPercent,
            $kebersamaanDivider,
            $kebersamaanOnlyUmum,
            $ecgNominal,
            $ecgDivider,
            $ecgDistributionMode,
            $poliPercent,
            $poliDistributionMode,
            $konsulWaNominal,
            $igdNominalPerPasien,
            $kehadiranNominalPerHadir,
            $jnsTindakanIds,
            $doctorRows,
            $ecgJnsTindakanIds,
            $poliJnsTindakanIds,
            $poliFilterDoctorRows,
            $poliFilterSourceRows,
            $poliFilterJnsTindakanIds,
            $konsulWaJnsTindakanIds,
            $rawatJalanMappingRows,
            $rawatJalanSpecialDoctorRows
        ) {
            $config = $this->getConfig();
            $now = now();
            $sourcePeriodMode = $this->normalizeSourcePeriodMode($sourcePeriodMode);
            $configPayload = [
                'visite_umum_percent' => $visiteUmumPercent,
                'visite_bpjs_percent' => $visiteBpjsPercent,
                'visite_bpjs_nominal' => $visiteBpjsNominal,
                'source_period_mode' => $sourcePeriodMode,
                'kebersamaan_umum_percent' => $kebersamaanUmumPercent,
                'kebersamaan_bpjs_nominal' => $kebersamaanBpjsNominal,
                'kebersamaan_bpjs_percent' => $kebersamaanBpjsPercent,
                'kebersamaan_divider' => max(1, $kebersamaanDivider),
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('generate_premi_dokter_configs', 'kebersamaan_only_umum')) {
                $configPayload['kebersamaan_only_umum'] = $kebersamaanOnlyUmum;
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_nominal')) {
                $configPayload['ecg_nominal'] = max(0, $ecgNominal);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_divider')) {
                $configPayload['ecg_divider'] = max(1, $ecgDivider);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'ecg_distribution_mode')) {
                $configPayload['ecg_distribution_mode'] = $this->normalizeEcgDistributionMode($ecgDistributionMode);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'poli_percent')) {
                $configPayload['poli_percent'] = max(0, $poliPercent);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'poli_distribution_mode')) {
                $configPayload['poli_distribution_mode'] = $this->normalizeEcgDistributionMode($poliDistributionMode);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'konsul_wa_nominal')) {
                $configPayload['konsul_wa_nominal'] = max(0, $konsulWaNominal);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'igd_nominal_per_pasien')) {
                $configPayload['igd_nominal_per_pasien'] = max(0, $igdNominalPerPasien);
            }

            if (Schema::hasColumn('generate_premi_dokter_configs', 'kehadiran_nominal_per_hadir')) {
                $configPayload['kehadiran_nominal_per_hadir'] = max(0, $kehadiranNominalPerHadir);
            }

            DB::table('generate_premi_dokter_configs')
                ->where('id', $config->id)
                ->update($configPayload);

            DB::table('generate_premi_dokter_config_tindakan')
                ->where('config_id', $config->id)
                ->delete();

            $mappingRows = collect($jnsTindakanIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->map(fn (int $id) => [
                    'config_id' => $config->id,
                    'jnsTindakan_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values();

            if ($mappingRows->isNotEmpty()) {
                DB::table('generate_premi_dokter_config_tindakan')->insert($mappingRows->all());
            }

            if (Schema::hasTable('generate_premi_dokter_rawat_jalan_config_tindakan')) {
                DB::table('generate_premi_dokter_rawat_jalan_config_tindakan')
                    ->where('config_id', $config->id)
                    ->delete();

                $rawatJalanMappingRows = collect($rawatJalanMappingRows)
                    ->map(fn (array $row) => [
                        'config_id' => $config->id,
                        'jnsTindakan_id' => (int) $row['jnsTindakan_id'],
                        'multiplier_type' => (string) $row['multiplier_type'],
                        'multiplier_value' => (float) $row['multiplier_value'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->filter(fn (array $row) => $row['jnsTindakan_id'] > 0)
                    ->unique(fn (array $row) => $row['jnsTindakan_id'])
                    ->values();

                if ($rawatJalanMappingRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_rawat_jalan_config_tindakan')->insert($rawatJalanMappingRows->all());
                }
            }

            if (Schema::hasTable('generate_premi_dokter_ecg_config_tindakan')) {
                DB::table('generate_premi_dokter_ecg_config_tindakan')
                    ->where('config_id', $config->id)
                    ->delete();

                $ecgMappingRows = collect($ecgJnsTindakanIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->map(fn (int $id) => [
                        'config_id' => $config->id,
                        'jnsTindakan_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values();

                if ($ecgMappingRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_ecg_config_tindakan')->insert($ecgMappingRows->all());
                }
            }

            if (Schema::hasTable('generate_premi_dokter_poli_config_tindakan')) {
                DB::table('generate_premi_dokter_poli_config_tindakan')
                    ->where('config_id', $config->id)
                    ->delete();

                $poliMappingRows = collect($poliJnsTindakanIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->map(fn (int $id) => [
                        'config_id' => $config->id,
                        'jnsTindakan_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values();

                if ($poliMappingRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_poli_config_tindakan')->insert($poliMappingRows->all());
                }
            }

            if (Schema::hasTable('generate_premi_dokter_poli_filter_doctor')) {
                DB::table('generate_premi_dokter_poli_filter_doctor')
                    ->where('config_id', $config->id)
                    ->delete();

                $poliFilterDoctorRows = collect($poliFilterDoctorRows)
                    ->map(fn (array $row) => [
                        'config_id' => $config->id,
                        'kd_dokter' => (string) $row['kd_dokter'],
                        'nm_dokter' => (string) $row['nm_dokter'],
                        'kd_sps' => $row['kd_sps'] ?? null,
                        'nm_sps' => $row['nm_sps'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->filter(fn (array $row) => filled($row['kd_dokter']))
                    ->unique(fn (array $row) => $row['kd_dokter'])
                    ->values();

                if ($poliFilterDoctorRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_poli_filter_doctor')->insert($poliFilterDoctorRows->all());
                }
            }

            if (Schema::hasTable('generate_premi_dokter_poli_filter_source')) {
                DB::table('generate_premi_dokter_poli_filter_source')
                    ->where('config_id', $config->id)
                    ->delete();

                $poliFilterSourceRows = collect($poliFilterSourceRows)
                    ->map(fn (array $row) => [
                        'config_id' => $config->id,
                        'source_table' => (string) $row['source_table'],
                        'source_label' => (string) $row['source_label'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->filter(fn (array $row) => filled($row['source_table']))
                    ->unique(fn (array $row) => $row['source_table'])
                    ->values();

                if ($poliFilterSourceRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_poli_filter_source')->insert($poliFilterSourceRows->all());
                }
            }

            if (Schema::hasTable('generate_premi_dokter_poli_filter_tindakan')) {
                DB::table('generate_premi_dokter_poli_filter_tindakan')
                    ->where('config_id', $config->id)
                    ->delete();

                $poliFilterMappingRows = collect($poliFilterJnsTindakanIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->map(fn (int $id) => [
                        'config_id' => $config->id,
                        'jnsTindakan_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values();

                if ($poliFilterMappingRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_poli_filter_tindakan')->insert($poliFilterMappingRows->all());
                }
            }

            if (Schema::hasTable('generate_premi_dokter_konsul_wa_config_tindakan')) {
                DB::table('generate_premi_dokter_konsul_wa_config_tindakan')
                    ->where('config_id', $config->id)
                    ->delete();

                $konsulWaMappingRows = collect($konsulWaJnsTindakanIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->map(fn (int $id) => [
                        'config_id' => $config->id,
                        'jnsTindakan_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values();

                if ($konsulWaMappingRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_konsul_wa_config_tindakan')->insert($konsulWaMappingRows->all());
                }
            }

            DB::table('generate_premi_dokter_config_doctor')
                ->where('config_id', $config->id)
                ->delete();

            $doctorRows = collect($doctorRows)
                ->map(fn (array $row) => [
                    'config_id' => $config->id,
                    'kategori' => (string) $row['kategori'],
                    'kd_dokter' => (string) $row['kd_dokter'],
                    'nm_dokter' => (string) $row['nm_dokter'],
                    'kd_sps' => $row['kd_sps'] ?? null,
                    'nm_sps' => $row['nm_sps'] ?? null,
                    'percent' => (float) $row['percent'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->unique(fn (array $row) => $row['kategori'].'|'.$row['kd_dokter'])
                ->values();

            if ($doctorRows->isNotEmpty()) {
                DB::table('generate_premi_dokter_config_doctor')->insert($doctorRows->all());
            }

            if (Schema::hasTable('generate_premi_dokter_rawat_jalan_special_doctor')) {
                DB::table('generate_premi_dokter_rawat_jalan_special_doctor')
                    ->where('config_id', $config->id)
                    ->delete();

                $rawatJalanSpecialDoctorRows = collect($rawatJalanSpecialDoctorRows)
                    ->map(fn (array $row) => [
                        'config_id' => $config->id,
                        'group_key' => (string) $row['group_key'],
                        'kd_dokter' => (string) $row['kd_dokter'],
                        'nm_dokter' => (string) $row['nm_dokter'],
                        'kd_sps' => $row['kd_sps'] ?? null,
                        'nm_sps' => $row['nm_sps'] ?? null,
                        'nominal' => (int) $row['nominal'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->filter(fn (array $row) => filled($row['kd_dokter']) && $row['nominal'] > 0)
                    ->unique(fn (array $row) => $row['group_key'].'|'.$row['kd_dokter'])
                    ->values();

                if ($rawatJalanSpecialDoctorRows->isNotEmpty()) {
                    DB::table('generate_premi_dokter_rawat_jalan_special_doctor')->insert($rawatJalanSpecialDoctorRows->all());
                }
            }

            return DB::table('generate_premi_dokter_configs')
                ->where('id', $config->id)
                ->first();
        });
    }

    public function getConfigTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_dokter_config_tindakan as ct')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ct.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->where('ct.config_id', $configId)
            ->select([
                'ct.jnsTindakan_id',
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->groupBy('ct.jnsTindakan_id', 'jt.id', 'jt.kode', 'jt.jenis')
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getConfiguredMappingTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_dokter_config_tindakan as ct')
            ->join('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'ct.jnsTindakan_id')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->where('ct.config_id', $configId)
            ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP'])
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mt.jnsTindakan_id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.nm_tindakan')
            ->get();
    }

    public function getRawatJalanConfigTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_rawat_jalan_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_rawat_jalan_config_tindakan as ct')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ct.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->where('ct.config_id', $configId)
            ->select([
                'ct.jnsTindakan_id',
                'ct.multiplier_type',
                'ct.multiplier_value',
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->groupBy(
                'ct.jnsTindakan_id',
                'ct.multiplier_type',
                'ct.multiplier_value',
                'jt.id',
                'jt.kode',
                'jt.jenis'
            )
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getConfiguredRawatJalanMappingTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_rawat_jalan_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_rawat_jalan_config_tindakan as ct')
            ->join('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'ct.jnsTindakan_id')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->where('ct.config_id', $configId)
            ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP'])
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mt.jnsTindakan_id',
                'ct.multiplier_type',
                'ct.multiplier_value',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.nm_tindakan')
            ->get();
    }

    public function getRawatJalanSpecialDoctors(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_rawat_jalan_special_doctor')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_rawat_jalan_special_doctor')
            ->where('config_id', $configId)
            ->orderByRaw("case group_key when 'rawat_jalan_khusus_45000' then 0 when 'rawat_jalan_khusus_72000' then 1 else 2 end")
            ->orderBy('nm_dokter')
            ->get();
    }

    public function getEcgConfigTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_ecg_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_ecg_config_tindakan as ct')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ct.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->where('ct.config_id', $configId)
            ->select([
                'ct.jnsTindakan_id',
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->groupBy('ct.jnsTindakan_id', 'jt.id', 'jt.kode', 'jt.jenis')
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getConfiguredEcgMappingTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_ecg_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_ecg_config_tindakan as ct')
            ->join('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'ct.jnsTindakan_id')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->where('ct.config_id', $configId)
            ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP'])
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mt.jnsTindakan_id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.nm_tindakan')
            ->get();
    }

    public function getPoliConfigTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_poli_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_poli_config_tindakan as ct')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ct.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->where('ct.config_id', $configId)
            ->select([
                'ct.jnsTindakan_id',
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->groupBy('ct.jnsTindakan_id', 'jt.id', 'jt.kode', 'jt.jenis')
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getConfiguredPoliMappingTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_poli_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_poli_config_tindakan as ct')
            ->join('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'ct.jnsTindakan_id')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->where('ct.config_id', $configId)
            ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP'])
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mt.jnsTindakan_id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.nm_tindakan')
            ->get();
    }

    public function getPoliFilterDoctors(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_poli_filter_doctor')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_poli_filter_doctor')
            ->where('config_id', $configId)
            ->orderBy('nm_dokter')
            ->get();
    }

    public function getPoliFilterSources(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_poli_filter_source')) {
            return $this->poliSourceTableOptions();
        }

        return DB::table('generate_premi_dokter_poli_filter_source')
            ->where('config_id', $configId)
            ->orderBy('source_table')
            ->get();
    }

    public function getPoliFilterTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_poli_filter_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_poli_filter_tindakan as ft')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ft.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->where('ft.config_id', $configId)
            ->select([
                'ft.jnsTindakan_id',
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->groupBy('ft.jnsTindakan_id', 'jt.id', 'jt.kode', 'jt.jenis')
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getPoliFilterTindakanIds(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_poli_filter_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_poli_filter_tindakan')
            ->where('config_id', $configId)
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function getKonsulWaConfigTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_konsul_wa_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_konsul_wa_config_tindakan as ct')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ct.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->where('ct.config_id', $configId)
            ->select([
                'ct.jnsTindakan_id',
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->groupBy('ct.jnsTindakan_id', 'jt.id', 'jt.kode', 'jt.jenis')
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getConfiguredKonsulWaMappingTindakan(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        if (! Schema::hasTable('generate_premi_dokter_konsul_wa_config_tindakan')) {
            return collect();
        }

        return DB::table('generate_premi_dokter_konsul_wa_config_tindakan as ct')
            ->join('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'ct.jnsTindakan_id')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->where('ct.config_id', $configId)
            ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP'])
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mt.jnsTindakan_id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.nm_tindakan')
            ->get();
    }

    public function getConfigDoctors(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_dokter_config_doctor')
            ->where('config_id', $configId)
            ->orderByRaw("case kategori when 'umum' then 0 when 'spesialis_65' then 1 when 'spesialis_80' then 2 when 'kebersamaan' then 3 when 'jasa_operasi' then 4 when 'jasa_rawat_jalan' then 5 when 'jasa_poli' then 6 when 'jasa_ecg' then 7 when 'konsul_wa' then 8 when 'jasa_igd' then 9 when 'kehadiran' then 10 else 11 end")
            ->orderBy('nm_dokter')
            ->get();
    }

    public function mappingTindakanOptions(?string $keyword = null): Collection
    {
        $limit = filled($keyword) ? 100 : 50;

        return DB::table('master_jenis_tindakan as jt')
            ->leftJoin('mapping_tindakan as mt', function ($join) {
                $join
                    ->on('mt.jnsTindakan_id', '=', 'jt.id')
                    ->whereIn('mt.sumber_tindakan', ['RAJAL', 'RANAP']);
            })
            ->select([
                'jt.id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
                DB::raw('COUNT(mt.id) as jumlah_mapping'),
            ])
            ->when($keyword, function ($query) use ($keyword) {
                $keyword = '%'.$keyword.'%';
                $query->where(function ($where) use ($keyword) {
                    $where
                        ->where('jt.kode', 'like', $keyword)
                        ->orWhere('jt.jenis', 'like', $keyword);
                });
            })
            ->groupBy('jt.id', 'jt.kode', 'jt.jenis')
            ->havingRaw('COUNT(mt.id) > 0')
            ->orderBy('jt.kode')
            ->orderBy('jt.jenis')
            ->limit($limit)
            ->get();
    }

    public function dokterOptions(?string $keyword = null): Collection
    {
        return DB::connection('mysql_khanza')
            ->table('dokter as d')
            ->leftJoin('spesialis as s', 's.kd_sps', '=', 'd.kd_sps')
            ->select([
                'd.kd_dokter',
                'd.nm_dokter',
                'd.kd_sps',
                's.nm_sps',
            ])
            ->where('d.status', '1')
            ->when($keyword, function ($query) use ($keyword) {
                $keyword = '%'.$keyword.'%';
                $query->where(function ($where) use ($keyword) {
                    $where
                        ->where('d.kd_dokter', 'like', $keyword)
                        ->orWhere('d.nm_dokter', 'like', $keyword)
                        ->orWhere('s.nm_sps', 'like', $keyword);
                });
            })
            ->orderBy('d.nm_dokter')
            ->limit(50)
            ->get();
    }

    public function getDoctorsByCodes(array $codes): Collection
    {
        $codes = collect($codes)
            ->map(fn ($code) => (string) $code)
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        $rows = collect();

        foreach ($codes->chunk(100) as $chunk) {
            $rows = $rows->merge(
                DB::connection('mysql_khanza')
                    ->table('dokter as d')
                    ->leftJoin('spesialis as s', 's.kd_sps', '=', 'd.kd_sps')
                    ->select([
                        'd.kd_dokter',
                        'd.nm_dokter',
                        'd.kd_sps',
                        's.nm_sps',
                    ])
                    ->whereIn('d.kd_dokter', $chunk->all())
                    ->get()
            );
        }

        return $rows;
    }

    public function calculate(string $periode, string $jenisPelayanan, object $config, bool $onlyDoctorUmum = false): array
    {
        $sourcePeriodMode = $jenisPelayanan === 'bpjs'
            ? $this->normalizeSourcePeriodMode($config->source_period_mode ?? null)
            : self::SOURCE_PERIOD_CURRENT;
        $sourcePeriode = $this->sourcePeriod($periode, $sourcePeriodMode);
        $range = $this->periodRange($sourcePeriode);
        $jenisTindakan = $this->getConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredMappingTindakan((int) $config->id);
        $allDoctorConfig = $this->getConfigDoctors((int) $config->id);
        $visiteDoctorConfig = $allDoctorConfig
            ->whereIn('kategori', self::VISITE_CATEGORIES)
            ->values();
        $doctorConfig = $jenisPelayanan === 'bpjs' || $onlyDoctorUmum
            ? $visiteDoctorConfig->where('kategori', self::CATEGORY_UMUM)->values()
            : $visiteDoctorConfig;
        $doctorByCode = $doctorConfig->keyBy('kd_dokter');
        $specialistCodes = $jenisPelayanan === 'bpjs' || $onlyDoctorUmum
            ? $visiteDoctorConfig
                ->whereIn('kategori', [self::CATEGORY_SPESIALIS_65, self::CATEGORY_SPESIALIS_80])
                ->pluck('kd_dokter')
                ->map(fn ($code) => (string) $code)
                ->values()
            : collect();
        $rows = $this->collectVisiteRows(
            $jenisPelayanan,
            $range['start'],
            $range['end'],
            $mappingTindakan,
            false
        );
        $ignoredSpecialistRows = $specialistCodes->isEmpty()
            ? collect()
            : $rows
                ->filter(fn (array $row) => $specialistCodes->contains((string) $row['kd_dokter']))
                ->values();
        $processableRows = $ignoredSpecialistRows->isEmpty()
            ? $rows
            : $rows
                ->reject(fn (array $row) => $specialistCodes->contains((string) $row['kd_dokter']))
                ->values();
        $unconfiguredRows = $processableRows
            ->reject(fn (array $row) => $doctorByCode->has((string) $row['kd_dokter']))
            ->values();
        $transactions = $processableRows
            ->filter(fn (array $row) => $doctorByCode->has((string) $row['kd_dokter']))
            ->values();
        $details = $transactions
            ->groupBy('kd_dokter')
            ->map(function (Collection $items, string $doctorCode) use ($jenisPelayanan, $config, $doctorByCode) {
                $doctor = $doctorByCode->get($doctorCode);
                $first = $items->first();
                $jumlahData = $items->count();
                $totalBiaya = round((float) $items->sum('biaya_rawat'), 2);
                $grandTotal = $jenisPelayanan === 'bpjs'
                    ? round($jumlahData * (int) $config->visite_bpjs_nominal, 2)
                    : $totalBiaya;
                $percent = $jenisPelayanan === 'bpjs'
                    ? (float) $config->visite_bpjs_percent
                    : (float) ($doctor->percent ?? $config->visite_umum_percent);

                return [
                    'kd_dokter' => $doctorCode,
                    'nm_dokter' => $doctor->nm_dokter ?? data_get($first, 'nm_dokter') ?? '-',
                    'kd_sps' => $doctor->kd_sps ?? data_get($first, 'kd_sps'),
                    'nm_sps' => $doctor->nm_sps ?? data_get($first, 'nm_sps'),
                    'kategori' => $doctor->kategori ?? self::CATEGORY_UMUM,
                    'percent' => round($percent, 4),
                    'jumlah_data' => $jumlahData,
                    'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => $totalBiaya,
                    'grand_total' => $grandTotal,
                    'total_premi' => round($grandTotal * ($percent / 100), 2),
                    'source_breakdown' => $this->breakdown(
                        $items,
                        fn ($row) => data_get($row, 'source_table'),
                        fn ($row, $key) => data_get($row, 'source_label') ?: $key
                    ),
                    'action_breakdown' => $this->breakdown(
                        $items,
                        fn ($row) => data_get($row, 'mapping_tindakan_id'),
                        fn ($row) => trim(data_get($row, 'kd_tindakan').' - '.data_get($row, 'nm_tindakan'))
                    ),
                    'data_rawat' => $items->values()->all(),
                ];
            })
            ->sortByDesc('total_premi')
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_VISITE,
            'jenis_pelayanan' => $jenisPelayanan,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => $transactions->pluck('kd_tindakan')->unique()->count(),
            'jumlah_jenis_tindakan' => $jenisTindakan->count(),
            'jumlah_mapping_tindakan' => $mappingTindakan->count(),
            'jumlah_tidak_terkonfigurasi' => $unconfiguredRows->count(),
            'jumlah_spesialis_diabaikan' => $ignoredSpecialistRows->count(),
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat'), 2),
            'total_grand' => round((float) $details->sum('grand_total'), 2),
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $rows->count(),
            'unconfigured_doctors' => $this->unconfiguredDoctorPayload($unconfiguredRows),
            'ignored_specialists' => $this->unconfiguredDoctorPayload($ignoredSpecialistRows),
            'config_snapshot' => [
                'jenis_tindakan' => $jenisTindakan->values()->all(),
                'mapping_tindakan' => $mappingTindakan->values()->all(),
                'doctor_config' => $doctorConfig->values()->all(),
                'all_doctor_config' => $allDoctorConfig->values()->all(),
                'source_tables' => collect(self::SOURCE_TABLES)->pluck('table')->values()->all(),
                'source_period_mode' => $sourcePeriodMode,
                'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
                'source_periode' => $sourcePeriode,
                'jumlah_spesialis_diabaikan' => $ignoredSpecialistRows->count(),
                'ignored_specialists' => $this->unconfiguredDoctorPayload($ignoredSpecialistRows),
                'visite_umum_percent' => (float) $config->visite_umum_percent,
                'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
                'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            ],
        ];
    }

    public function calculateKebersamaan(string $periode, object $config): array
    {
        $kebersamaanOnlyUmum = (bool) ($config->kebersamaan_only_umum ?? false);
        $umumCalculation = $this->calculate($periode, 'umum', $config, $kebersamaanOnlyUmum);
        $bpjsCalculation = $this->calculate($periode, 'bpjs', $config, $kebersamaanOnlyUmum);
        $bpjsSourcePeriodMode = $this->normalizeSourcePeriodMode($config->source_period_mode ?? null);
        $bpjsSourcePeriode = $bpjsCalculation['source_periode'];
        $bpjsRange = $this->periodRange($bpjsSourcePeriode);
        $periodeRange = $this->periodRange($periode);
        $jenisTindakan = $this->getConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredMappingTindakan((int) $config->id);
        $doctorConfig = $this->getConfigDoctors((int) $config->id)
            ->where('kategori', self::CATEGORY_KEBERSAMAAN)
            ->values();

        $kebersamaanUmumPercent = (float) ($config->kebersamaan_umum_percent ?? 30);
        $kebersamaanBpjsNominal = (int) ($config->kebersamaan_bpjs_nominal ?? 40000);
        $kebersamaanBpjsPercent = (float) ($config->kebersamaan_bpjs_percent ?? 30);
        $kebersamaanDivider = max(1, (int) ($config->kebersamaan_divider ?? 4));
        $sumberDokterLabel = $kebersamaanOnlyUmum ? 'Dokter Umum' : 'Dokter Umum & Spesialis';
        $visiteUmumTotalPremi = round((float) $umumCalculation['total_premi'], 2);
        $kebersamaanVisiteUmum = round($visiteUmumTotalPremi * ($kebersamaanUmumPercent / 100), 2);
        $bpjsJumlahTransaksi = (int) $bpjsCalculation['jumlah_transaksi'];
        $bpjsJumlahPasien = (int) $bpjsCalculation['jumlah_pasien'];
        $bpjsJumlahTindakan = (int) $bpjsCalculation['jumlah_tindakan'];
        $bpjsDasarHitung = round($bpjsJumlahTransaksi * $kebersamaanBpjsNominal, 2);
        $kebersamaanVisiteBpjs = round($bpjsDasarHitung * ($kebersamaanBpjsPercent / 100), 2);
        $grandTotalKebersamaan = round($kebersamaanVisiteUmum + $kebersamaanVisiteBpjs, 2);
        $allocationPercent = round(100 / $kebersamaanDivider, 4);
        $allocationPerDoctor = round($grandTotalKebersamaan / $kebersamaanDivider, 2);
        $jumlahTransaksi = (int) $umumCalculation['jumlah_transaksi'] + $bpjsJumlahTransaksi;
        $jumlahPasien = (int) $umumCalculation['jumlah_pasien'] + $bpjsJumlahPasien;
        $ignoredSpecialistRows = collect($umumCalculation['ignored_specialists'] ?? [])
            ->merge($bpjsCalculation['ignored_specialists'] ?? [])
            ->values();
        $ignoredSpecialistCount = (int) ($umumCalculation['jumlah_spesialis_diabaikan'] ?? 0)
            + (int) ($bpjsCalculation['jumlah_spesialis_diabaikan'] ?? 0);
        $sourceBreakdown = [
            [
                'key' => 'visite_umum',
                'label' => 'Kebersamaan Visite UMUM - '.$sumberDokterLabel,
                'jumlah_data' => (int) $umumCalculation['jumlah_transaksi'],
                'jumlah_pasien' => (int) $umumCalculation['jumlah_pasien'],
                'total_biaya_rawat' => $kebersamaanVisiteUmum,
            ],
            [
                'key' => 'visite_bpjs',
                'label' => 'Kebersamaan Visite BPJS - '.$sumberDokterLabel,
                'jumlah_data' => $bpjsJumlahTransaksi,
                'jumlah_pasien' => $bpjsJumlahPasien,
                'total_biaya_rawat' => $kebersamaanVisiteBpjs,
            ],
        ];
        $actionBreakdown = [
            [
                'key' => 'formula_visite_umum',
                'label' => 'Total premi Jasa Visite UMUM '.$sumberDokterLabel.' x '.$kebersamaanUmumPercent.'%',
                'jumlah_data' => (int) $umumCalculation['jumlah_transaksi'],
                'jumlah_pasien' => (int) $umumCalculation['jumlah_pasien'],
                'total_biaya_rawat' => $kebersamaanVisiteUmum,
            ],
            [
                'key' => 'formula_visite_bpjs',
                'label' => 'Jumlah transaksi BPJS x '.$kebersamaanBpjsNominal.' x '.$kebersamaanBpjsPercent.'%',
                'jumlah_data' => $bpjsJumlahTransaksi,
                'jumlah_pasien' => $bpjsJumlahPasien,
                'total_biaya_rawat' => $kebersamaanVisiteBpjs,
            ],
        ];
        $formulaRows = [
            [
                'source_table' => 'kebersamaan_visite_umum',
                'sumber_tindakan' => 'VISITE_UMUM',
                'source_label' => 'Kebersamaan Visite UMUM - '.$sumberDokterLabel,
                'mapping_tindakan_id' => null,
                'jnsTindakan_id' => null,
                'kode_jenis_tindakan' => 'VISITE_UMUM',
                'nama_jenis_tindakan' => 'Kebersamaan Visite UMUM',
                'no_rawat' => 'KB-UMUM-'.$periode,
                'no_rkm_medis' => null,
                'nm_pasien' => 'Total premi Jasa Visite UMUM',
                'kd_pj' => 'UMUM',
                'nama_penjamin' => 'UMUM',
                'tanggal' => $umumCalculation['source_tgl_akhir'],
                'jam' => null,
                'kd_tindakan' => 'VISITE_UMUM',
                'nm_tindakan' => 'Total premi Jasa Visite UMUM '.$sumberDokterLabel.' x '.$kebersamaanUmumPercent.'%',
                'kd_dokter' => null,
                'nm_dokter' => null,
                'kd_sps' => null,
                'nm_sps' => null,
                'doctor_source' => 'formula',
                'nip' => null,
                'nama_petugas' => null,
                'biaya_rawat' => $kebersamaanVisiteUmum,
            ],
            [
                'source_table' => 'kebersamaan_visite_bpjs',
                'sumber_tindakan' => 'VISITE_BPJS',
                'source_label' => 'Kebersamaan Visite BPJS - '.$sumberDokterLabel,
                'mapping_tindakan_id' => null,
                'jnsTindakan_id' => null,
                'kode_jenis_tindakan' => 'VISITE_BPJS',
                'nama_jenis_tindakan' => 'Kebersamaan Visite BPJS',
                'no_rawat' => 'KB-BPJS-'.$bpjsSourcePeriode,
                'no_rkm_medis' => null,
                'nm_pasien' => 'Jumlah transaksi Jasa Visite BPJS',
                'kd_pj' => 'BPJ',
                'nama_penjamin' => 'BPJS',
                'tanggal' => $bpjsRange['end']->copy()->subDay()->toDateString(),
                'jam' => null,
                'kd_tindakan' => 'VISITE_BPJS',
                'nm_tindakan' => $bpjsJumlahTransaksi.' transaksi x '.$kebersamaanBpjsNominal.' x '.$kebersamaanBpjsPercent.'%',
                'kd_dokter' => null,
                'nm_dokter' => null,
                'kd_sps' => null,
                'nm_sps' => null,
                'doctor_source' => 'formula',
                'nip' => null,
                'nama_petugas' => null,
                'biaya_rawat' => $kebersamaanVisiteBpjs,
            ],
        ];
        $details = $doctorConfig
            ->map(fn ($doctor) => [
                'kd_dokter' => $doctor->kd_dokter,
                'nm_dokter' => $doctor->nm_dokter,
                'kd_sps' => $doctor->kd_sps,
                'nm_sps' => $doctor->nm_sps,
                'kategori' => self::CATEGORY_KEBERSAMAAN,
                'percent' => $allocationPercent,
                'jumlah_data' => $jumlahTransaksi,
                'jumlah_pasien' => $jumlahPasien,
                'total_biaya_rawat' => round($visiteUmumTotalPremi + $bpjsDasarHitung, 2),
                'grand_total' => $grandTotalKebersamaan,
                'total_premi' => $allocationPerDoctor,
                'source_breakdown' => $sourceBreakdown,
                'action_breakdown' => $actionBreakdown,
                'data_rawat' => $formulaRows,
            ])
            ->sortBy('nm_dokter')
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $periode,
            'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
            'source_period_mode_label' => 'Periode Berjalan',
            'source_period_text' => 'UMUM '.$umumCalculation['source_periode'].' / BPJS '.$bpjsSourcePeriode.' ('.$this->sourcePeriodModeLabel($bpjsSourcePeriodMode).') / '.$sumberDokterLabel,
            'source_tgl_awal' => $periodeRange['start']->toDateString(),
            'source_tgl_akhir' => $periodeRange['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_KEBERSAMAAN,
            'jenis_pelayanan' => self::SERVICE_KEBERSAMAAN,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'kebersamaan_umum_percent' => $kebersamaanUmumPercent,
            'kebersamaan_bpjs_nominal' => $kebersamaanBpjsNominal,
            'kebersamaan_bpjs_percent' => $kebersamaanBpjsPercent,
            'kebersamaan_divider' => $kebersamaanDivider,
            'kebersamaan_only_umum' => $kebersamaanOnlyUmum,
            'kebersamaan_sumber_dokter_label' => $sumberDokterLabel,
            'kebersamaan_allocation_percent' => $allocationPercent,
            'kebersamaan_allocation_per_doctor' => $allocationPerDoctor,
            'kebersamaan_visite_umum_total_premi' => $visiteUmumTotalPremi,
            'kebersamaan_visite_umum_total' => $kebersamaanVisiteUmum,
            'kebersamaan_visite_bpjs_jumlah_transaksi' => $bpjsJumlahTransaksi,
            'kebersamaan_visite_bpjs_jumlah_tindakan' => $bpjsJumlahTindakan,
            'kebersamaan_visite_bpjs_dasar_hitung' => $bpjsDasarHitung,
            'kebersamaan_visite_bpjs_total' => $kebersamaanVisiteBpjs,
            'jumlah_transaksi' => $jumlahTransaksi,
            'jumlah_pasien' => $jumlahPasien,
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => (int) $umumCalculation['jumlah_tindakan'] + $bpjsJumlahTindakan,
            'jumlah_jenis_tindakan' => $jenisTindakan->count(),
            'jumlah_mapping_tindakan' => $mappingTindakan->count(),
            'jumlah_tidak_terkonfigurasi' => 0,
            'jumlah_spesialis_diabaikan' => $ignoredSpecialistCount,
            'total_biaya_rawat' => round($visiteUmumTotalPremi + $bpjsDasarHitung, 2),
            'total_grand' => $grandTotalKebersamaan,
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $jumlahTransaksi,
            'unconfigured_doctors' => [],
            'ignored_specialists' => $ignoredSpecialistRows->all(),
            'config_snapshot' => [
                'jenis_tindakan' => $jenisTindakan->values()->all(),
                'mapping_tindakan' => $mappingTindakan->values()->all(),
                'doctor_config' => $doctorConfig->values()->all(),
                'source_tables' => collect(self::SOURCE_TABLES)->pluck('table')->values()->all(),
                'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
                'source_period_mode_label' => 'Periode Berjalan',
                'source_periode' => $periode,
                'source_period_text' => 'UMUM '.$umumCalculation['source_periode'].' / BPJS '.$bpjsSourcePeriode.' ('.$this->sourcePeriodModeLabel($bpjsSourcePeriodMode).')',
                'bpjs_source_period_mode' => $bpjsSourcePeriodMode,
                'bpjs_source_period_mode_label' => $this->sourcePeriodModeLabel($bpjsSourcePeriodMode),
                'bpjs_source_periode' => $bpjsSourcePeriode,
                'kebersamaan_umum_percent' => $kebersamaanUmumPercent,
                'kebersamaan_bpjs_nominal' => $kebersamaanBpjsNominal,
                'kebersamaan_bpjs_percent' => $kebersamaanBpjsPercent,
                'kebersamaan_divider' => $kebersamaanDivider,
                'kebersamaan_only_umum' => $kebersamaanOnlyUmum,
                'kebersamaan_sumber_dokter_label' => $sumberDokterLabel,
                'kebersamaan_allocation_percent' => $allocationPercent,
                'kebersamaan_allocation_per_doctor' => $allocationPerDoctor,
                'kebersamaan_visite_umum_total_premi' => $visiteUmumTotalPremi,
                'kebersamaan_visite_umum_total' => $kebersamaanVisiteUmum,
                'kebersamaan_visite_bpjs_jumlah_transaksi' => $bpjsJumlahTransaksi,
                'kebersamaan_visite_bpjs_jumlah_tindakan' => $bpjsJumlahTindakan,
                'kebersamaan_visite_bpjs_dasar_hitung' => $bpjsDasarHitung,
                'kebersamaan_visite_bpjs_total' => $kebersamaanVisiteBpjs,
                'kebersamaan_grand_total' => $grandTotalKebersamaan,
                'kebersamaan_formula_rows' => $formulaRows,
                'jumlah_spesialis_diabaikan' => $ignoredSpecialistCount,
                'ignored_specialists' => $ignoredSpecialistRows->all(),
            ],
        ];
    }

    public function calculateOperasi(string $periode, object $config, int $nominalOperasi): array
    {
        $nominalOperasi = max(0, $nominalOperasi);
        $range = $this->periodRange($periode);
        $doctorConfig = $this->getConfigDoctors((int) $config->id)
            ->where('kategori', self::CATEGORY_OPERASI)
            ->values();
        $sourceBreakdown = [
            [
                'key' => 'manual_jasa_operasi',
                'label' => 'Input Manual Jasa Operasi',
                'jumlah_data' => 1,
                'jumlah_pasien' => 0,
                'total_biaya_rawat' => $nominalOperasi,
            ],
        ];
        $totalPercent = round((float) $doctorConfig->sum('percent'), 4);

        $details = $doctorConfig
            ->map(function ($doctor) use ($periode, $nominalOperasi, $sourceBreakdown) {
                $percent = (float) $doctor->percent;
                $totalPremi = round($nominalOperasi * ($percent / 100), 2);
                $formulaLabel = 'Nominal operasi x '.$percent.'%';
                $manualRows = [
                    [
                        'source_table' => 'manual_jasa_operasi',
                        'sumber_tindakan' => 'MANUAL',
                        'source_label' => 'Input Manual Jasa Operasi',
                        'mapping_tindakan_id' => null,
                        'jnsTindakan_id' => null,
                        'kode_jenis_tindakan' => 'JASA_OPERASI',
                        'nama_jenis_tindakan' => 'Jasa Operasi',
                        'no_rawat' => 'OPERASI-'.$periode.'-'.$doctor->kd_dokter,
                        'no_rkm_medis' => null,
                        'nm_pasien' => 'Input manual jasa operasi',
                        'kd_pj' => 'MANUAL',
                        'nama_penjamin' => 'Manual',
                        'tanggal' => Carbon::createFromFormat('Y-m-d', $periode.'-01')->endOfMonth()->toDateString(),
                        'jam' => null,
                        'kd_tindakan' => 'JASA_OPERASI',
                        'nm_tindakan' => $formulaLabel,
                        'kd_dokter' => $doctor->kd_dokter,
                        'nm_dokter' => $doctor->nm_dokter,
                        'kd_sps' => $doctor->kd_sps,
                        'nm_sps' => $doctor->nm_sps,
                        'doctor_source' => 'config',
                        'nip' => null,
                        'nama_petugas' => null,
                        'biaya_rawat' => $nominalOperasi,
                    ],
                ];

                return [
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'kd_sps' => $doctor->kd_sps,
                    'nm_sps' => $doctor->nm_sps,
                    'kategori' => self::CATEGORY_OPERASI,
                    'percent' => round($percent, 4),
                    'jumlah_data' => 1,
                    'jumlah_pasien' => 0,
                    'total_biaya_rawat' => $nominalOperasi,
                    'grand_total' => $nominalOperasi,
                    'total_premi' => $totalPremi,
                    'source_breakdown' => $sourceBreakdown,
                    'action_breakdown' => [
                        [
                            'key' => 'formula_jasa_operasi',
                            'label' => $formulaLabel,
                            'jumlah_data' => 1,
                            'jumlah_pasien' => 0,
                            'total_biaya_rawat' => $totalPremi,
                        ],
                    ],
                    'data_rawat' => $manualRows,
                ];
            })
            ->sortByDesc('total_premi')
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $periode,
            'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
            'source_period_mode_label' => 'Input Manual',
            'source_period_text' => 'Input manual periode '.$periode,
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_OPERASI,
            'jenis_pelayanan' => self::SERVICE_MANUAL,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'nominal_operasi' => $nominalOperasi,
            'operasi_total_percent' => $totalPercent,
            'jumlah_transaksi' => $nominalOperasi > 0 ? 1 : 0,
            'jumlah_pasien' => 0,
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => 1,
            'jumlah_jenis_tindakan' => 0,
            'jumlah_mapping_tindakan' => 0,
            'jumlah_tidak_terkonfigurasi' => 0,
            'jumlah_spesialis_diabaikan' => 0,
            'total_biaya_rawat' => $nominalOperasi,
            'total_grand' => $nominalOperasi,
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $nominalOperasi > 0 ? 1 : 0,
            'unconfigured_doctors' => [],
            'ignored_specialists' => [],
            'config_snapshot' => [
                'jenis_tindakan' => [],
                'mapping_tindakan' => [],
                'doctor_config' => $doctorConfig->values()->all(),
                'source_tables' => ['manual_jasa_operasi'],
                'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
                'source_period_mode_label' => 'Input Manual',
                'source_periode' => $periode,
                'source_period_text' => 'Input manual periode '.$periode,
                'nominal_operasi' => $nominalOperasi,
                'operasi_total_percent' => $totalPercent,
            ],
        ];
    }

    public function calculateManualVolume(
        string $periode,
        object $config,
        string $jenisPremiDokter,
        array $doctorRows
    ): array {
        $isIgd = $jenisPremiDokter === self::TYPE_IGD;
        $type = $isIgd ? self::TYPE_IGD : self::TYPE_KEHADIRAN;
        $category = $isIgd ? self::CATEGORY_IGD : self::CATEGORY_KEHADIRAN;
        $label = $isIgd ? 'Jasa IGD' : 'Kehadiran';
        $code = $isIgd ? 'JASA_IGD' : 'KEHADIRAN';
        $countLabel = $isIgd ? 'pasien' : 'kehadiran';
        $sourceKey = $isIgd ? 'manual_jasa_igd' : 'manual_kehadiran';
        $sourceLabel = $isIgd ? 'Input Manual Jasa IGD' : 'Input Manual Kehadiran';
        $nominal = max(0, (int) ($isIgd
            ? ($config->igd_nominal_per_pasien ?? 30000)
            : ($config->kehadiran_nominal_per_hadir ?? 250000)));
        $range = $this->periodRange($periode);
        $date = Carbon::createFromFormat('Y-m-d', $periode.'-01')->endOfMonth()->toDateString();
        $inputRows = collect($doctorRows)
            ->map(function (array $doctor) {
                $doctor['jumlah'] = max(0, (int) ($doctor['jumlah'] ?? 0));

                return $doctor;
            })
            ->filter(fn (array $doctor) => filled($doctor['kd_dokter'] ?? null) && $doctor['jumlah'] > 0)
            ->values();

        $details = $inputRows
            ->map(function (array $doctor) use (
                $periode,
                $category,
                $label,
                $code,
                $countLabel,
                $sourceKey,
                $sourceLabel,
                $nominal,
                $date,
                $isIgd
            ) {
                $jumlah = (int) $doctor['jumlah'];
                $totalPremi = round($jumlah * $nominal, 2);
                $formulaLabel = $jumlah.' '.$countLabel.' x Rp '.number_format($nominal, 0, ',', '.');
                $sourceBreakdown = [
                    [
                        'key' => $sourceKey,
                        'label' => $sourceLabel,
                        'jumlah_data' => $jumlah,
                        'jumlah_pasien' => $isIgd ? $jumlah : 0,
                        'total_biaya_rawat' => $totalPremi,
                    ],
                ];
                $manualRows = [
                    [
                        'source_table' => $sourceKey,
                        'sumber_tindakan' => 'MANUAL',
                        'source_label' => $sourceLabel,
                        'mapping_tindakan_id' => null,
                        'jnsTindakan_id' => null,
                        'kode_jenis_tindakan' => $code,
                        'nama_jenis_tindakan' => $label,
                        'no_rawat' => $code.'-'.$periode.'-'.$doctor['kd_dokter'],
                        'no_rkm_medis' => null,
                        'nm_pasien' => $sourceLabel,
                        'kd_pj' => 'MANUAL',
                        'nama_penjamin' => 'Manual',
                        'tanggal' => $date,
                        'jam' => null,
                        'kd_tindakan' => $code,
                        'nm_tindakan' => $formulaLabel,
                        'kd_dokter' => $doctor['kd_dokter'],
                        'nm_dokter' => $doctor['nm_dokter'],
                        'kd_sps' => $doctor['kd_sps'] ?? null,
                        'nm_sps' => $doctor['nm_sps'] ?? null,
                        'doctor_source' => 'manual_generate',
                        'nip' => null,
                        'nama_petugas' => null,
                        'jumlah_manual' => $jumlah,
                        'nominal_manual' => $nominal,
                        'biaya_rawat' => $totalPremi,
                    ],
                ];

                return [
                    'kd_dokter' => $doctor['kd_dokter'],
                    'nm_dokter' => $doctor['nm_dokter'],
                    'kd_sps' => $doctor['kd_sps'] ?? null,
                    'nm_sps' => $doctor['nm_sps'] ?? null,
                    'kategori' => $category,
                    'percent' => 100,
                    'jumlah_data' => $jumlah,
                    'jumlah_pasien' => $isIgd ? $jumlah : 0,
                    'total_biaya_rawat' => $totalPremi,
                    'grand_total' => $totalPremi,
                    'total_premi' => $totalPremi,
                    'source_breakdown' => $sourceBreakdown,
                    'action_breakdown' => [
                        [
                            'key' => strtolower($code),
                            'label' => $formulaLabel,
                            'jumlah_data' => $jumlah,
                            'jumlah_pasien' => $isIgd ? $jumlah : 0,
                            'total_biaya_rawat' => $totalPremi,
                        ],
                    ],
                    'data_rawat' => $manualRows,
                ];
            })
            ->sortByDesc('total_premi')
            ->values();

        $jumlahTransaksi = (int) $details->sum('jumlah_data');
        $totalPremi = round((float) $details->sum('total_premi'), 2);

        return [
            'periode' => $periode,
            'source_periode' => $periode,
            'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
            'source_period_mode_label' => 'Input Manual',
            'source_period_text' => 'Input manual periode '.$periode,
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => $type,
            'jenis_pelayanan' => self::SERVICE_MANUAL,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'igd_nominal_per_pasien' => (int) ($config->igd_nominal_per_pasien ?? 30000),
            'kehadiran_nominal_per_hadir' => (int) ($config->kehadiran_nominal_per_hadir ?? 250000),
            'manual_nominal' => $nominal,
            'manual_doctor_count' => $details->count(),
            'manual_count_label' => $countLabel,
            'manual_type_label' => $label,
            'jumlah_transaksi' => $jumlahTransaksi,
            'jumlah_pasien' => $isIgd ? $jumlahTransaksi : 0,
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => $jumlahTransaksi > 0 ? 1 : 0,
            'jumlah_jenis_tindakan' => 0,
            'jumlah_mapping_tindakan' => 0,
            'jumlah_tidak_terkonfigurasi' => 0,
            'jumlah_spesialis_diabaikan' => 0,
            'total_biaya_rawat' => $totalPremi,
            'total_grand' => $totalPremi,
            'total_premi' => $totalPremi,
            'details' => $details,
            'all_rows_count' => $jumlahTransaksi,
            'unconfigured_doctors' => [],
            'ignored_specialists' => [],
            'config_snapshot' => [
                'jenis_tindakan' => [],
                'mapping_tindakan' => [],
                'doctor_config' => $inputRows->values()->all(),
                'source_tables' => [$sourceKey],
                'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
                'source_period_mode_label' => 'Input Manual',
                'source_periode' => $periode,
                'source_period_text' => 'Input manual periode '.$periode,
                'igd_nominal_per_pasien' => (int) ($config->igd_nominal_per_pasien ?? 30000),
                'kehadiran_nominal_per_hadir' => (int) ($config->kehadiran_nominal_per_hadir ?? 250000),
                'manual_nominal' => $nominal,
                'manual_doctor_count' => $details->count(),
                'manual_count_label' => $countLabel,
                'manual_type_label' => $label,
            ],
        ];
    }

    public function calculateRawatJalan(string $periode, string $jenisPelayanan, object $config): array
    {
        $jenisPelayanan = $jenisPelayanan === 'bpjs' ? 'bpjs' : 'umum';
        $sourcePeriodMode = $jenisPelayanan === 'bpjs'
            ? $this->normalizeSourcePeriodMode($config->source_period_mode ?? null)
            : self::SOURCE_PERIOD_CURRENT;
        $sourcePeriode = $this->sourcePeriod($periode, $sourcePeriodMode);
        $range = $this->periodRange($sourcePeriode);
        $jenisTindakan = $this->getRawatJalanConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredRawatJalanMappingTindakan((int) $config->id);
        $normalDoctorConfig = $this->getConfigDoctors((int) $config->id)
            ->where('kategori', self::CATEGORY_RAWAT_JALAN)
            ->values();
        $specialDoctorConfig = $this->getRawatJalanSpecialDoctors((int) $config->id)
            ->values();
        $specialDoctorByCode = $specialDoctorConfig
            ->groupBy('kd_dokter')
            ->map(fn (Collection $rows) => $rows->first());
        $normalDoctorByCode = $normalDoctorConfig
            ->reject(fn ($doctor) => $specialDoctorByCode->has((string) $doctor->kd_dokter))
            ->keyBy('kd_dokter');
        $rows = $this->collectVisiteRows(
            $jenisPelayanan,
            $range['start'],
            $range['end'],
            $mappingTindakan,
            false
        );

        $normalTransactions = $rows
            ->filter(fn (array $row) => $normalDoctorByCode->has((string) $row['kd_dokter']))
            ->map(function (array $row) {
                $row['biaya_rawat_asli'] = $row['biaya_rawat'];
                $row['premi_rawat_jalan'] = $this->rawatJalanPremiumValue($row);
                $row['multiplier_label'] = $this->rawatJalanMultiplierLabel($row);

                return $row;
            })
            ->values();
        $specialTransactions = $rows
            ->filter(fn (array $row) => $specialDoctorByCode->has((string) $row['kd_dokter']))
            ->map(function (array $row) use ($specialDoctorByCode) {
                $doctor = $specialDoctorByCode->get((string) $row['kd_dokter']);
                $nominal = max(0, (int) ($doctor->nominal ?? 0));

                $row['biaya_rawat_asli'] = $row['biaya_rawat'];
                $row['premi_rawat_jalan'] = $nominal;
                $row['multiplier_type'] = self::MULTIPLIER_NOMINAL;
                $row['multiplier_value'] = $nominal;
                $row['multiplier_label'] = 'Khusus '.$this->rupiahText($nominal).' per data';

                return $row;
            })
            ->values();
        $configuredDoctorCodes = $normalDoctorByCode
            ->keys()
            ->merge($specialDoctorByCode->keys())
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();
        $unconfiguredRows = $rows
            ->reject(fn (array $row) => $configuredDoctorCodes->contains((string) $row['kd_dokter']))
            ->values();

        $normalDetails = $normalTransactions
            ->groupBy('kd_dokter')
            ->map(function (Collection $items, string $doctorCode) use ($normalDoctorByCode) {
                $doctor = $normalDoctorByCode->get($doctorCode);
                $first = $items->first();
                $grandTotal = round((float) $items->sum('premi_rawat_jalan'), 2);

                return [
                    'kd_dokter' => $doctorCode,
                    'nm_dokter' => $doctor->nm_dokter ?? data_get($first, 'nm_dokter') ?? '-',
                    'kd_sps' => $doctor->kd_sps ?? data_get($first, 'kd_sps'),
                    'nm_sps' => $doctor->nm_sps ?? data_get($first, 'nm_sps'),
                    'kategori' => self::CATEGORY_RAWAT_JALAN,
                    'percent' => 100,
                    'jumlah_data' => $items->count(),
                    'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round((float) $items->sum('biaya_rawat_asli'), 2),
                    'grand_total' => $grandTotal,
                    'total_premi' => $grandTotal,
                    'source_breakdown' => $this->rawatJalanBreakdown(
                        $items,
                        fn ($row) => data_get($row, 'source_table'),
                        fn ($row, $key) => data_get($row, 'source_label') ?: $key
                    ),
                    'action_breakdown' => $this->rawatJalanBreakdown(
                        $items,
                        fn ($row) => data_get($row, 'jnsTindakan_id'),
                        fn ($row, $key) => trim(
                            data_get($row, 'kode_jenis_tindakan').' - '
                            .data_get($row, 'nama_jenis_tindakan').' ('
                            .data_get($row, 'multiplier_label').')'
                        )
                    ),
                    'data_rawat' => $items->values()->all(),
                ];
            })
            ->values();
        $specialDetails = $specialTransactions
            ->groupBy('kd_dokter')
            ->map(function (Collection $items, string $doctorCode) use ($specialDoctorByCode) {
                $doctor = $specialDoctorByCode->get($doctorCode);
                $nominal = max(0, (int) ($doctor->nominal ?? 0));
                $grandTotal = round($items->count() * $nominal, 2);

                return [
                    'kd_dokter' => $doctorCode,
                    'nm_dokter' => $doctor->nm_dokter ?? data_get($items->first(), 'nm_dokter') ?? '-',
                    'kd_sps' => $doctor->kd_sps ?? data_get($items->first(), 'kd_sps'),
                    'nm_sps' => $doctor->nm_sps ?? data_get($items->first(), 'nm_sps'),
                    'kategori' => $doctor->group_key ?? self::CATEGORY_RAWAT_JALAN_SPECIAL_45000,
                    'percent' => 100,
                    'jumlah_data' => $items->count(),
                    'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round((float) $items->sum('biaya_rawat_asli'), 2),
                    'grand_total' => $grandTotal,
                    'total_premi' => $grandTotal,
                    'source_breakdown' => $this->rawatJalanBreakdown(
                        $items,
                        fn ($row) => data_get($row, 'source_table'),
                        fn ($row, $key) => data_get($row, 'source_label') ?: $key
                    ),
                    'action_breakdown' => $this->rawatJalanBreakdown(
                        $items,
                        fn ($row) => data_get($row, 'jnsTindakan_id'),
                        fn ($row, $key) => trim(data_get($row, 'kode_jenis_tindakan').' - '.data_get($row, 'nama_jenis_tindakan'))
                            .' (khusus '.$this->rupiahText($nominal).' per data)'
                    ),
                    'data_rawat' => $items->values()->all(),
                ];
            })
            ->values();
        $details = $normalDetails
            ->merge($specialDetails)
            ->sortByDesc('total_premi')
            ->values();
        $transactions = $normalTransactions
            ->merge($specialTransactions)
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
            'source_period_text' => 'Sumber rawat '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_RAWAT_JALAN,
            'jenis_pelayanan' => $jenisPelayanan,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'rawat_jalan_mapping_config_count' => $jenisTindakan->count(),
            'rawat_jalan_special_doctor_count' => $specialDoctorConfig->count(),
            'rawat_jalan_doctor_config_count' => $normalDoctorConfig->count(),
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => $transactions->pluck('kd_tindakan')->unique()->count(),
            'jumlah_jenis_tindakan' => $jenisTindakan->count(),
            'jumlah_mapping_tindakan' => $mappingTindakan->count(),
            'jumlah_tidak_terkonfigurasi' => $unconfiguredRows->count(),
            'jumlah_spesialis_diabaikan' => 0,
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat_asli'), 2),
            'total_grand' => round((float) $details->sum('grand_total'), 2),
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $rows->count(),
            'unconfigured_doctors' => $this->unconfiguredDoctorPayload($unconfiguredRows),
            'ignored_specialists' => [],
            'config_snapshot' => [
                'jenis_tindakan' => $jenisTindakan->values()->all(),
                'mapping_tindakan' => $mappingTindakan->values()->all(),
                'rawat_jalan_mapping_config' => $jenisTindakan->values()->all(),
                'doctor_config' => $normalDoctorConfig->values()->all(),
                'rawat_jalan_special_doctors' => $specialDoctorConfig->values()->all(),
                'source_tables' => collect(self::SOURCE_TABLES)->pluck('table')->values()->all(),
                'source_period_mode' => $sourcePeriodMode,
                'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
                'source_periode' => $sourcePeriode,
                'source_period_text' => 'Sumber rawat '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
                'jenis_pelayanan' => $jenisPelayanan,
                'rawat_jalan_mapping_config_count' => $jenisTindakan->count(),
                'rawat_jalan_special_doctor_count' => $specialDoctorConfig->count(),
                'rawat_jalan_doctor_config_count' => $normalDoctorConfig->count(),
            ],
        ];
    }

    public function calculateEcg(string $periode, string $jenisPelayanan, object $config): array
    {
        $jenisPelayanan = $jenisPelayanan === 'bpjs' ? 'bpjs' : 'umum';
        $sourcePeriodMode = $jenisPelayanan === 'bpjs'
            ? $this->normalizeSourcePeriodMode($config->source_period_mode ?? null)
            : self::SOURCE_PERIOD_CURRENT;
        $sourcePeriode = $this->sourcePeriod($periode, $sourcePeriodMode);
        $range = $this->periodRange($sourcePeriode);
        $jenisTindakan = $this->getEcgConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredEcgMappingTindakan((int) $config->id);
        $doctorConfig = $this->getConfigDoctors((int) $config->id)
            ->where('kategori', self::CATEGORY_ECG)
            ->values();
        $ecgNominal = max(0, (int) ($config->ecg_nominal ?? 5000));
        $ecgDivider = max(1, (int) ($config->ecg_divider ?? 3));
        $ecgDistributionMode = $this->normalizeEcgDistributionMode($config->ecg_distribution_mode ?? null);
        $isFullAmount = $ecgDistributionMode === self::ECG_DISTRIBUTION_FULL_AMOUNT;
        $rows = $this->collectVisiteRows(
            $jenisPelayanan,
            $range['start'],
            $range['end'],
            $mappingTindakan,
            false
        );
        $transactions = $rows
            ->map(function (array $row) use ($ecgNominal, $ecgDivider) {
                $row['biaya_rawat_asli'] = $row['biaya_rawat'];
                $row['premi_ecg'] = round($ecgNominal / $ecgDivider, 2);
                $row['multiplier_label'] = $this->rupiahText($ecgNominal).' / '.$ecgDivider.' per data';

                return $row;
            })
            ->values();
        $jumlahData = $transactions->count();
        $grandTotal = round(($jumlahData * $ecgNominal) / $ecgDivider, 2);
        $doctorCount = $doctorConfig->count();
        $allocationPercent = $doctorCount > 0
            ? ($isFullAmount ? 100 : round(100 / $doctorCount, 4))
            : 0;
        $allocationPerDoctor = $doctorCount > 0
            ? ($isFullAmount ? $grandTotal : round($grandTotal / $doctorCount, 2))
            : 0;
        $sourceBreakdown = $this->ecgBreakdown(
            $transactions,
            fn ($row) => data_get($row, 'source_table'),
            fn ($row, $key) => data_get($row, 'source_label') ?: $key,
            $ecgNominal,
            $ecgDivider
        );
        $actionBreakdown = $this->ecgBreakdown(
            $transactions,
            fn ($row) => data_get($row, 'jnsTindakan_id'),
            fn ($row, $key) => trim(
                data_get($row, 'kode_jenis_tindakan').' - '
                .data_get($row, 'nama_jenis_tindakan').' ('
                .data_get($row, 'multiplier_label').')'
            ),
            $ecgNominal,
            $ecgDivider
        );

        $details = $doctorConfig
            ->map(function ($doctor) use (
                $transactions,
                $grandTotal,
                $allocationPercent,
                $allocationPerDoctor,
                $sourceBreakdown,
                $actionBreakdown
            ) {
                return [
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'kd_sps' => $doctor->kd_sps,
                    'nm_sps' => $doctor->nm_sps,
                    'kategori' => self::CATEGORY_ECG,
                    'percent' => $allocationPercent,
                    'jumlah_data' => $transactions->count(),
                    'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat_asli'), 2),
                    'grand_total' => $grandTotal,
                    'total_premi' => $allocationPerDoctor,
                    'source_breakdown' => $sourceBreakdown,
                    'action_breakdown' => $actionBreakdown,
                    'data_rawat' => $transactions->all(),
                ];
            })
            ->sortBy('nm_dokter')
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
            'source_period_text' => 'Sumber ECG '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_ECG,
            'jenis_pelayanan' => $jenisPelayanan,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'ecg_nominal' => $ecgNominal,
            'ecg_divider' => $ecgDivider,
            'ecg_distribution_mode' => $ecgDistributionMode,
            'ecg_distribution_mode_label' => $this->ecgDistributionModeLabel($ecgDistributionMode),
            'ecg_doctor_config_count' => $doctorCount,
            'ecg_allocation_percent' => $allocationPercent,
            'ecg_allocation_per_doctor' => $allocationPerDoctor,
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => $transactions->pluck('kd_tindakan')->unique()->count(),
            'jumlah_jenis_tindakan' => $jenisTindakan->count(),
            'jumlah_mapping_tindakan' => $mappingTindakan->count(),
            'jumlah_tidak_terkonfigurasi' => 0,
            'jumlah_spesialis_diabaikan' => 0,
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat_asli'), 2),
            'total_grand' => $grandTotal,
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $rows->count(),
            'unconfigured_doctors' => [],
            'ignored_specialists' => [],
            'config_snapshot' => [
                'jenis_tindakan' => $jenisTindakan->values()->all(),
                'mapping_tindakan' => $mappingTindakan->values()->all(),
                'doctor_config' => $doctorConfig->values()->all(),
                'source_tables' => collect(self::SOURCE_TABLES)->pluck('table')->values()->all(),
                'source_period_mode' => $sourcePeriodMode,
                'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
                'source_periode' => $sourcePeriode,
                'source_period_text' => 'Sumber ECG '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
                'jenis_pelayanan' => $jenisPelayanan,
                'ecg_nominal' => $ecgNominal,
                'ecg_divider' => $ecgDivider,
                'ecg_distribution_mode' => $ecgDistributionMode,
                'ecg_distribution_mode_label' => $this->ecgDistributionModeLabel($ecgDistributionMode),
                'ecg_doctor_config_count' => $doctorCount,
                'ecg_allocation_percent' => $allocationPercent,
                'ecg_allocation_per_doctor' => $allocationPerDoctor,
            ],
        ];
    }

    public function calculatePoli(string $periode, string $jenisPelayanan, object $config): array
    {
        $jenisPelayanan = $jenisPelayanan === 'bpjs' ? 'bpjs' : 'umum';
        $sourcePeriodMode = $jenisPelayanan === 'bpjs'
            ? $this->normalizeSourcePeriodMode($config->source_period_mode ?? null)
            : self::SOURCE_PERIOD_CURRENT;
        $sourcePeriode = $this->sourcePeriod($periode, $sourcePeriodMode);
        $range = $this->periodRange($sourcePeriode);
        $jenisTindakan = $this->getPoliConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredPoliMappingTindakan((int) $config->id);
        $doctorConfig = $this->getConfigDoctors((int) $config->id)
            ->where('kategori', self::CATEGORY_POLI)
            ->values();
        $filterDoctors = $this->getPoliFilterDoctors((int) $config->id)->values();
        $filterDoctorCodes = $filterDoctors
            ->pluck('kd_dokter')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();
        $filterSources = $this->getPoliFilterSources((int) $config->id)->values();
        $filterSourceTables = $filterSources
            ->pluck('source_table')
            ->map(fn ($table) => (string) $table)
            ->unique()
            ->values();
        $filterTindakan = $this->getPoliFilterTindakan((int) $config->id);
        $filterTindakanIds = $filterTindakan
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $filteredMappingTindakan = $filterTindakanIds->isEmpty()
            ? collect()
            : $mappingTindakan
                ->filter(fn ($mapping) => $filterTindakanIds->contains((int) $mapping->jnsTindakan_id))
                ->values();
        $poliPercent = max(0, (float) ($config->poli_percent ?? 30));
        $poliDistributionMode = $this->normalizeEcgDistributionMode($config->poli_distribution_mode ?? null);
        $isFullAmount = $poliDistributionMode === self::ECG_DISTRIBUTION_FULL_AMOUNT;
        $rows = $filterDoctorCodes->isEmpty() || $filterSourceTables->isEmpty() || $filteredMappingTindakan->isEmpty()
            ? collect()
            : $this->collectVisiteRows(
                $jenisPelayanan,
                $range['start'],
                $range['end'],
                $filteredMappingTindakan,
                true,
                $filterDoctorCodes,
                $filterSourceTables
            );
        $transactions = $rows
            ->map(function (array $row) use ($poliPercent) {
                $row['biaya_rawat_asli'] = $row['biaya_rawat'];
                $row['premi_poli'] = round((float) $row['biaya_rawat'] * ($poliPercent / 100), 2);
                $row['multiplier_label'] = number_format($poliPercent, 2, ',', '.').'% dari biaya rawat';

                return $row;
            })
            ->values();
        $totalBiayaRawat = round((float) $transactions->sum('biaya_rawat_asli'), 2);
        $grandTotal = round($totalBiayaRawat * ($poliPercent / 100), 2);
        $doctorCount = $doctorConfig->count();
        $allocationPercent = $doctorCount > 0
            ? ($isFullAmount ? 100 : round(100 / $doctorCount, 4))
            : 0;
        $allocationPerDoctor = $doctorCount > 0
            ? ($isFullAmount ? $grandTotal : round($grandTotal / $doctorCount, 2))
            : 0;

        $details = $doctorConfig
            ->map(function ($doctor) use (
                $transactions,
                $totalBiayaRawat,
                $grandTotal,
                $allocationPercent,
                $allocationPerDoctor
            ) {
                return [
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'kd_sps' => $doctor->kd_sps,
                    'nm_sps' => $doctor->nm_sps,
                    'kategori' => self::CATEGORY_POLI,
                    'percent' => $allocationPercent,
                    'jumlah_data' => $transactions->count(),
                    'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => $totalBiayaRawat,
                    'grand_total' => $grandTotal,
                    'total_premi' => $allocationPerDoctor,
                    'source_breakdown' => $this->breakdown(
                        $transactions,
                        fn ($row) => data_get($row, 'source_table'),
                        fn ($row, $key) => data_get($row, 'source_label') ?: $key
                    ),
                    'action_breakdown' => $this->breakdown(
                        $transactions,
                        fn ($row) => data_get($row, 'jnsTindakan_id'),
                        fn ($row, $key) => trim(
                            data_get($row, 'kode_jenis_tindakan').' - '
                            .data_get($row, 'nama_jenis_tindakan').' ('
                            .data_get($row, 'multiplier_label').')'
                        )
                    ),
                    'data_rawat' => $transactions->all(),
                ];
            })
            ->sortBy('nm_dokter')
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
            'source_period_text' => 'Sumber Poli '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_POLI,
            'jenis_pelayanan' => $jenisPelayanan,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'poli_percent' => $poliPercent,
            'poli_distribution_mode' => $poliDistributionMode,
            'poli_distribution_mode_label' => $this->ecgDistributionModeLabel($poliDistributionMode),
            'poli_doctor_config_count' => $doctorCount,
            'poli_filter_doctor_count' => $filterDoctors->count(),
            'poli_filter_source_count' => $filterSources->count(),
            'poli_filter_tindakan_count' => $filterTindakan->count(),
            'poli_allocation_percent' => $allocationPercent,
            'poli_allocation_per_doctor' => $allocationPerDoctor,
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => $transactions->pluck('kd_tindakan')->unique()->count(),
            'jumlah_jenis_tindakan' => $jenisTindakan->count(),
            'jumlah_mapping_tindakan' => $mappingTindakan->count(),
            'jumlah_tidak_terkonfigurasi' => 0,
            'jumlah_spesialis_diabaikan' => 0,
            'total_biaya_rawat' => $totalBiayaRawat,
            'total_grand' => $grandTotal,
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $rows->count(),
            'unconfigured_doctors' => [],
            'ignored_specialists' => [],
            'config_snapshot' => [
                'jenis_tindakan' => $jenisTindakan->values()->all(),
                'mapping_tindakan' => $mappingTindakan->values()->all(),
                'doctor_config' => $doctorConfig->values()->all(),
                'poli_filter_doctors' => $filterDoctors->values()->all(),
                'poli_filter_sources' => $filterSources->values()->all(),
                'poli_filter_tindakan' => $filterTindakan->values()->all(),
                'source_tables' => $filterSourceTables->values()->all(),
                'source_period_mode' => $sourcePeriodMode,
                'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
                'source_periode' => $sourcePeriode,
                'source_period_text' => 'Sumber Poli '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
                'jenis_pelayanan' => $jenisPelayanan,
                'poli_percent' => $poliPercent,
                'poli_distribution_mode' => $poliDistributionMode,
                'poli_distribution_mode_label' => $this->ecgDistributionModeLabel($poliDistributionMode),
                'poli_doctor_config_count' => $doctorCount,
                'poli_filter_doctor_count' => $filterDoctors->count(),
                'poli_filter_source_count' => $filterSources->count(),
                'poli_filter_tindakan_count' => $filterTindakan->count(),
                'poli_allocation_percent' => $allocationPercent,
                'poli_allocation_per_doctor' => $allocationPerDoctor,
            ],
        ];
    }

    public function calculateKonsulWa(string $periode, string $jenisPelayanan, object $config): array
    {
        $jenisPelayanan = $jenisPelayanan === 'bpjs' ? 'bpjs' : 'umum';
        $sourcePeriodMode = $jenisPelayanan === 'bpjs'
            ? $this->normalizeSourcePeriodMode($config->source_period_mode ?? null)
            : self::SOURCE_PERIOD_CURRENT;
        $sourcePeriode = $this->sourcePeriod($periode, $sourcePeriodMode);
        $range = $this->periodRange($sourcePeriode);
        $jenisTindakan = $this->getKonsulWaConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredKonsulWaMappingTindakan((int) $config->id);
        $doctorConfig = $this->getConfigDoctors((int) $config->id)
            ->where('kategori', self::CATEGORY_KONSUL_WA)
            ->values();
        $doctorByCode = $doctorConfig->keyBy('kd_dokter');
        $konsulWaNominal = max(0, (int) ($config->konsul_wa_nominal ?? 0));
        $selectedDoctorCodes = $doctorByCode
            ->keys()
            ->map(fn ($code) => (string) $code)
            ->values();
        $rows = $selectedDoctorCodes->isEmpty()
            ? collect()
            : $this->collectVisiteRows(
                $jenisPelayanan,
                $range['start'],
                $range['end'],
                $mappingTindakan,
                true,
                $selectedDoctorCodes
            );
        $transactions = $rows
            ->filter(fn (array $row) => $doctorByCode->has((string) $row['kd_dokter']))
            ->map(function (array $row) use ($konsulWaNominal) {
                $row['biaya_rawat_asli'] = $row['biaya_rawat'];
                $row['premi_konsul_wa'] = $konsulWaNominal;
                $row['multiplier_label'] = $this->rupiahText($konsulWaNominal).' per data';

                return $row;
            })
            ->values();
        $unconfiguredRows = $rows
            ->reject(fn (array $row) => $doctorByCode->has((string) $row['kd_dokter']))
            ->values();

        $details = $transactions
            ->groupBy('kd_dokter')
            ->map(function (Collection $items, string $doctorCode) use ($doctorByCode, $konsulWaNominal) {
                $doctor = $doctorByCode->get($doctorCode);
                $grandTotal = round($items->count() * $konsulWaNominal, 2);

                return [
                    'kd_dokter' => $doctorCode,
                    'nm_dokter' => $doctor->nm_dokter ?? data_get($items->first(), 'nm_dokter') ?? '-',
                    'kd_sps' => $doctor->kd_sps ?? data_get($items->first(), 'kd_sps'),
                    'nm_sps' => $doctor->nm_sps ?? data_get($items->first(), 'nm_sps'),
                    'kategori' => self::CATEGORY_KONSUL_WA,
                    'percent' => 100,
                    'jumlah_data' => $items->count(),
                    'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round((float) $items->sum('biaya_rawat_asli'), 2),
                    'grand_total' => $grandTotal,
                    'total_premi' => $grandTotal,
                    'source_breakdown' => $this->ecgBreakdown(
                        $items,
                        fn ($row) => data_get($row, 'source_table'),
                        fn ($row, $key) => data_get($row, 'source_label') ?: $key,
                        $konsulWaNominal,
                        1
                    ),
                    'action_breakdown' => $this->ecgBreakdown(
                        $items,
                        fn ($row) => data_get($row, 'jnsTindakan_id'),
                        fn ($row, $key) => trim(
                            data_get($row, 'kode_jenis_tindakan').' - '
                            .data_get($row, 'nama_jenis_tindakan').' ('
                            .data_get($row, 'multiplier_label').')'
                        ),
                        $konsulWaNominal,
                        1
                    ),
                    'data_rawat' => $items->values()->all(),
                ];
            })
            ->sortByDesc('total_premi')
            ->values();

        return [
            'periode' => $periode,
            'source_periode' => $sourcePeriode,
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
            'source_period_text' => 'Sumber Konsul WA '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jenis_premi_dokter' => self::TYPE_KONSUL_WA,
            'jenis_pelayanan' => $jenisPelayanan,
            'visite_umum_percent' => (float) $config->visite_umum_percent,
            'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
            'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
            'konsul_wa_nominal' => $konsulWaNominal,
            'konsul_wa_doctor_config_count' => $doctorConfig->count(),
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_dokter' => $details->count(),
            'jumlah_tindakan' => $transactions->pluck('kd_tindakan')->unique()->count(),
            'jumlah_jenis_tindakan' => $jenisTindakan->count(),
            'jumlah_mapping_tindakan' => $mappingTindakan->count(),
            'jumlah_tidak_terkonfigurasi' => $unconfiguredRows->count(),
            'jumlah_spesialis_diabaikan' => 0,
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat_asli'), 2),
            'total_grand' => round((float) $details->sum('grand_total'), 2),
            'total_premi' => round((float) $details->sum('total_premi'), 2),
            'details' => $details,
            'all_rows_count' => $rows->count(),
            'unconfigured_doctors' => $this->unconfiguredDoctorPayload($unconfiguredRows),
            'ignored_specialists' => [],
            'config_snapshot' => [
                'jenis_tindakan' => $jenisTindakan->values()->all(),
                'mapping_tindakan' => $mappingTindakan->values()->all(),
                'doctor_config' => $doctorConfig->values()->all(),
                'source_tables' => collect(self::SOURCE_TABLES)->pluck('table')->values()->all(),
                'source_period_mode' => $sourcePeriodMode,
                'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
                'source_periode' => $sourcePeriode,
                'source_period_text' => 'Sumber Konsul WA '.$sourcePeriode.' ('.$this->sourcePeriodModeLabel($sourcePeriodMode).')',
                'jenis_pelayanan' => $jenisPelayanan,
                'konsul_wa_nominal' => $konsulWaNominal,
                'konsul_wa_doctor_config_count' => $doctorConfig->count(),
            ],
        ];
    }

    public function saveResult(
        string $periode,
        string $jenisPelayanan,
        object $config,
        array $calculation,
        string $jenisPremiDokter = self::TYPE_VISITE
    ): generatePremiDokterModel {
        return DB::transaction(function () use ($periode, $jenisPelayanan, $config, $calculation, $jenisPremiDokter) {
            $existing = $this->findByPeriodAndTypeForUpdate($periode, $jenisPelayanan, $jenisPremiDokter);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Premi dokter {$jenisPremiDokter} periode {$periode} sudah dikunci.",
                ]);
            }

            $payload = [
                'source_periode' => $calculation['source_periode'],
                'source_period_mode' => $calculation['source_period_mode'],
                'source_tgl_awal' => $calculation['source_tgl_awal'],
                'source_tgl_akhir' => $calculation['source_tgl_akhir'],
                'visite_umum_percent' => (float) $config->visite_umum_percent,
                'visite_bpjs_percent' => (float) $config->visite_bpjs_percent,
                'visite_bpjs_nominal' => (int) $config->visite_bpjs_nominal,
                'jumlah_transaksi' => $calculation['jumlah_transaksi'],
                'jumlah_pasien' => $calculation['jumlah_pasien'],
                'jumlah_dokter' => $calculation['jumlah_dokter'],
                'jumlah_tindakan' => $calculation['jumlah_tindakan'],
                'jumlah_mapping_tindakan' => $calculation['jumlah_mapping_tindakan'],
                'jumlah_tidak_terkonfigurasi' => $calculation['jumlah_tidak_terkonfigurasi'],
                'total_biaya_rawat' => $calculation['total_biaya_rawat'],
                'total_grand' => $calculation['total_grand'],
                'total_premi' => $calculation['total_premi'],
                'config_snapshot' => $calculation['config_snapshot'],
                'generate_by' => Auth::id(),
            ];

            $header = generatePremiDokterModel::query()->updateOrCreate(
                [
                    'periode' => $periode,
                    'jenis_premi_dokter' => $jenisPremiDokter,
                    'jenis_pelayanan' => $jenisPelayanan,
                ],
                $payload
            );

            $header->details()->delete();

            $detailRows = collect($calculation['details'])
                ->map(fn (array $detail) => [
                    'kd_dokter' => $detail['kd_dokter'],
                    'nm_dokter' => $detail['nm_dokter'],
                    'kd_sps' => $detail['kd_sps'],
                    'nm_sps' => $detail['nm_sps'],
                    'kategori' => $detail['kategori'],
                    'percent' => $detail['percent'],
                    'jumlah_data' => $detail['jumlah_data'],
                    'jumlah_pasien' => $detail['jumlah_pasien'],
                    'total_biaya_rawat' => $detail['total_biaya_rawat'],
                    'grand_total' => $detail['grand_total'],
                    'total_premi' => $detail['total_premi'],
                    'source_breakdown' => $detail['source_breakdown'],
                    'action_breakdown' => $detail['action_breakdown'],
                    'data_rawat' => $detail['data_rawat'],
                ])
                ->values();

            if ($detailRows->isNotEmpty()) {
                $header->details()->createMany($detailRows->all());
            }

            return $this->findById((int) $header->id);
        });
    }

    public function findByPeriodAndType(
        string $periode,
        string $jenisPelayanan,
        string $jenisPremiDokter = self::TYPE_VISITE
    ): ?generatePremiDokterModel {
        return generatePremiDokterModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->with(['details' => fn ($query) => $query->orderByDesc('total_premi')])
            ->where('periode', $periode)
            ->where('jenis_premi_dokter', $jenisPremiDokter)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->first();
    }

    public function findByPeriodAndTypeForUpdate(
        string $periode,
        string $jenisPelayanan,
        string $jenisPremiDokter = self::TYPE_VISITE
    ): ?generatePremiDokterModel {
        return generatePremiDokterModel::query()
            ->where('periode', $periode)
            ->where('jenis_premi_dokter', $jenisPremiDokter)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->lockForUpdate()
            ->first();
    }

    public function findById(int $id): generatePremiDokterModel
    {
        return generatePremiDokterModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->with(['details' => fn ($query) => $query->orderByDesc('total_premi')])
            ->findOrFail($id);
    }

    public function lock(int $id, ?int $userId): generatePremiDokterModel
    {
        $header = generatePremiDokterModel::query()->findOrFail($id);
        $header->forceFill([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $userId,
        ])->save();

        return $this->findById($id);
    }

    public function unlock(int $id): generatePremiDokterModel
    {
        $header = generatePremiDokterModel::query()->findOrFail($id);
        $header->forceFill([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ])->save();

        return $this->findById($id);
    }

    private function collectVisiteRows(
        string $jenisPelayanan,
        Carbon $start,
        Carbon $end,
        Collection $mappingTindakan,
        bool $requireDoctor = true,
        $doctorCodes = null,
        $sourceTables = null
    ): Collection {
        if ($mappingTindakan->isEmpty()) {
            return collect();
        }

        $doctorCodes = collect($doctorCodes ?? [])
            ->map(fn ($code) => (string) $code)
            ->filter()
            ->unique()
            ->values();
        $sourceTables = collect($sourceTables ?? [])
            ->map(fn ($table) => (string) $table)
            ->filter()
            ->unique()
            ->values();
        $mappingBySourceCode = $mappingTindakan
            ->groupBy(fn ($item) => $item->sumber_tindakan.'|'.$item->kd_tindakan);
        $codesBySource = $mappingTindakan
            ->groupBy('sumber_tindakan')
            ->map(fn (Collection $items) => $items->pluck('kd_tindakan')->unique()->values());
        $rows = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            if ($sourceTables->isNotEmpty() && ! $sourceTables->contains($definition['table'])) {
                continue;
            }

            $codes = $codesBySource->get($definition['source'], collect());

            if ($codes->isEmpty()) {
                continue;
            }

            $rawRows = $this->sourceQuery($definition, $jenisPelayanan, $start, $end, $codes, $doctorCodes)->get();

            foreach ($rawRows as $row) {
                $key = $row->sumber_tindakan.'|'.$row->kd_tindakan;
                $matches = $mappingBySourceCode->get($key, collect());

                foreach ($matches as $mapping) {
                    if ($requireDoctor && ! filled($row->kd_dokter)) {
                        continue;
                    }

                    $rows->push([
                        'source_table' => $row->source_table,
                        'sumber_tindakan' => $row->sumber_tindakan,
                        'source_label' => $row->source_label,
                        'mapping_tindakan_id' => (int) $mapping->mapping_tindakan_id,
                        'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
                        'kode_jenis_tindakan' => $mapping->kode_jenis_tindakan,
                        'nama_jenis_tindakan' => $mapping->nama_jenis_tindakan,
                        'multiplier_type' => $mapping->multiplier_type ?? null,
                        'multiplier_value' => isset($mapping->multiplier_value) ? (float) $mapping->multiplier_value : null,
                        'no_rawat' => $row->no_rawat,
                        'no_rkm_medis' => $row->no_rkm_medis,
                        'nm_pasien' => $row->nm_pasien,
                        'kd_pj' => $row->kd_pj,
                        'nama_penjamin' => $row->nama_penjamin,
                        'tanggal' => $this->cleanDate($row->tanggal),
                        'jam' => $this->cleanTime($row->jam),
                        'kd_tindakan' => $row->kd_tindakan,
                        'nm_tindakan' => $row->nm_tindakan,
                        'kd_dokter' => (string) $row->kd_dokter,
                        'nm_dokter' => $row->nm_dokter,
                        'kd_sps' => $row->kd_sps,
                        'nm_sps' => $row->nm_sps,
                        'doctor_source' => $row->doctor_source,
                        'nip' => $row->nip,
                        'nama_petugas' => $row->nama_petugas,
                        'biaya_rawat' => round((float) $row->biaya_rawat, 2),
                    ]);
                }
            }
        }

        return $rows
            ->unique(fn (array $row) => implode('|', [
                $row['source_table'],
                $row['mapping_tindakan_id'],
                $row['no_rawat'],
                $row['tanggal'],
                $row['jam'],
                $row['kd_tindakan'],
                $row['kd_dokter'],
                $row['nip'] ?? '',
                $row['biaya_rawat'],
            ]))
            ->sortBy(fn (array $row) => implode('|', [
                $row['tanggal'],
                $row['jam'],
                $row['no_rawat'],
                $row['source_table'],
                $row['kd_tindakan'],
            ]))
            ->values();
    }

    private function sourceQuery(
        array $definition,
        string $jenisPelayanan,
        Carbon $start,
        Carbon $end,
        Collection $codes,
        Collection $doctorCodes
    ) {
        $hasDoctorColumn = in_array($definition['provider'], ['dr', 'drpr'], true);
        $hasPetugas = in_array($definition['provider'], ['pr', 'drpr'], true);
        $doctorColumn = $hasDoctorColumn ? 'r.kd_dokter' : 'rp.kd_dokter';
        $doctorSource = $hasDoctorColumn ? 'rawat' : 'reg_periksa';

        $query = DB::connection('mysql_khanza')
            ->table($definition['table'].' as r')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'r.no_rawat')
            ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->leftJoin($definition['master'].' as jp', 'jp.kd_jenis_prw', '=', 'r.kd_jenis_prw')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', DB::raw($doctorColumn))
            ->leftJoin('spesialis as s', 's.kd_sps', '=', 'd.kd_sps')
            ->select([
                DB::raw("'{$definition['table']}' as source_table"),
                DB::raw("'{$definition['source']}' as sumber_tindakan"),
                DB::raw("'{$definition['label']}' as source_label"),
                'r.no_rawat',
                'rp.no_rkm_medis',
                'ps.nm_pasien',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
                'r.tgl_perawatan as tanggal',
                'r.jam_rawat as jam',
                'r.kd_jenis_prw as kd_tindakan',
                DB::raw('COALESCE(jp.nm_perawatan, r.kd_jenis_prw) as nm_tindakan'),
                DB::raw($doctorColumn.' as kd_dokter'),
                'd.nm_dokter',
                'd.kd_sps',
                's.nm_sps',
                DB::raw("'{$doctorSource}' as doctor_source"),
                'r.biaya_rawat',
            ])
            ->where('r.tgl_perawatan', '>=', $start->toDateString())
            ->where('r.tgl_perawatan', '<', $end->toDateString())
            ->whereIn('r.kd_jenis_prw', $codes->values()->all())
            ->where('r.biaya_rawat', '>', 0);

        if ($doctorCodes->isNotEmpty()) {
            $query->whereIn($doctorColumn, $doctorCodes->all());
        }

        if ($hasPetugas) {
            $query->leftJoin('petugas as pt', 'pt.nip', '=', 'r.nip')
                ->selectRaw('r.nip as nip')
                ->selectRaw('pt.nama as nama_petugas');
        } else {
            $query->selectRaw('null as nip')
                ->selectRaw('null as nama_petugas');
        }

        if ($jenisPelayanan === 'bpjs') {
            return $this->applyBpjsFilter($query);
        }

        if ($jenisPelayanan === 'umum') {
            return $this->applyUmumFilter($query);
        }

        return $query;
    }

    private function applyUmumFilter($query)
    {
        return $query
            ->whereNotIn('rp.kd_pj', ['BPJ', '-', ''])
            ->where(function ($where) {
                $where
                    ->where(function ($nonA09) {
                        $nonA09
                            ->where('rp.kd_pj', '!=', 'A09')
                            ->whereExists(function ($piutang) {
                                $piutang
                                    ->selectRaw('1')
                                    ->from('piutang_pasien as pp')
                                    ->whereColumn('pp.no_rawat', 'rp.no_rawat')
                                    ->where('pp.status', 'Belum Lunas');
                            });
                    })
                    ->orWhere(function ($a09) {
                        $a09
                            ->where('rp.kd_pj', 'A09')
                            ->where('rp.status_bayar', 'Sudah Bayar');
                    });
            });
    }

    private function applyBpjsFilter($query)
    {
        return $query
            ->where('rp.kd_pj', 'BPJ')
            ->whereExists(function ($piutang) {
                $piutang
                    ->selectRaw('1')
                    ->from('piutang_pasien as pp')
                    ->whereColumn('pp.no_rawat', 'rp.no_rawat')
                    ->where('pp.status', 'Belum Lunas');
            });
    }

    private function breakdown(Collection $items, callable $keyResolver, callable $labelResolver): array
    {
        return $items
            ->groupBy(fn ($row) => $keyResolver($row) ?: '-')
            ->map(function (Collection $rows, string|int $key) use ($labelResolver) {
                $first = $rows->first();

                return [
                    'key' => (string) $key,
                    'label' => $labelResolver($first, $key) ?: (string) $key,
                    'jumlah_data' => $rows->count(),
                    'jumlah_pasien' => $rows->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round((float) $rows->sum('biaya_rawat'), 2),
                ];
            })
            ->sortByDesc('total_biaya_rawat')
            ->values()
            ->all();
    }

    private function rawatJalanBreakdown(Collection $items, callable $keyResolver, callable $labelResolver): array
    {
        return $items
            ->groupBy(fn ($row) => $keyResolver($row) ?: '-')
            ->map(function (Collection $rows, string|int $key) use ($labelResolver) {
                $first = $rows->first();

                return [
                    'key' => (string) $key,
                    'label' => $labelResolver($first, $key) ?: (string) $key,
                    'jumlah_data' => $rows->count(),
                    'jumlah_pasien' => $rows->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round((float) $rows->sum('premi_rawat_jalan'), 2),
                ];
            })
            ->sortByDesc('total_biaya_rawat')
            ->values()
            ->all();
    }

    private function ecgBreakdown(Collection $items, callable $keyResolver, callable $labelResolver, int $nominal, int $divider): array
    {
        $divider = max(1, $divider);

        return $items
            ->groupBy(fn ($row) => $keyResolver($row) ?: '-')
            ->map(function (Collection $rows, string|int $key) use ($labelResolver, $nominal, $divider) {
                $first = $rows->first();

                return [
                    'key' => (string) $key,
                    'label' => $labelResolver($first, $key) ?: (string) $key,
                    'jumlah_data' => $rows->count(),
                    'jumlah_pasien' => $rows->pluck('no_rawat')->unique()->count(),
                    'total_biaya_rawat' => round(($rows->count() * $nominal) / $divider, 2),
                ];
            })
            ->sortByDesc('total_biaya_rawat')
            ->values()
            ->all();
    }

    private function rawatJalanPremiumValue(array $row): float
    {
        $type = (string) ($row['multiplier_type'] ?? self::MULTIPLIER_NOMINAL);
        $value = max(0, (float) ($row['multiplier_value'] ?? 0));

        if ($type === self::MULTIPLIER_PERCENT) {
            return round((float) $row['biaya_rawat'] * ($value / 100), 2);
        }

        return round($value, 2);
    }

    private function rawatJalanMultiplierLabel(array $row): string
    {
        $type = (string) ($row['multiplier_type'] ?? self::MULTIPLIER_NOMINAL);
        $value = max(0, (float) ($row['multiplier_value'] ?? 0));

        if ($type === self::MULTIPLIER_PERCENT) {
            return number_format($value, 2, ',', '.').'% dari biaya rawat';
        }

        return $this->rupiahText($value).' per data';
    }

    private function rupiahText(float|int $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    private function unconfiguredDoctorPayload(Collection $rows): array
    {
        return $rows
            ->groupBy('kd_dokter')
            ->map(function (Collection $items, string $code) {
                $first = $items->first();

                return [
                    'kd_dokter' => $code,
                    'nm_dokter' => data_get($first, 'nm_dokter') ?: '-',
                    'nm_sps' => data_get($first, 'nm_sps'),
                    'jumlah_data' => $items->count(),
                    'total_biaya_rawat' => round((float) $items->sum('biaya_rawat'), 2),
                ];
            })
            ->sortByDesc('jumlah_data')
            ->take(20)
            ->values()
            ->all();
    }

    private function periodRange(string $periode): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfDay();

        return [
            'start' => $start,
            'end' => $start->copy()->addMonthNoOverflow()->startOfDay(),
        ];
    }

    private function sourcePeriod(string $periode, string $mode): string
    {
        $source = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        if ($mode === self::SOURCE_PERIOD_PREVIOUS) {
            $source->subMonth();
        }

        return $source->format('Y-m');
    }

    private function normalizeSourcePeriodMode(?string $mode): string
    {
        return $mode === self::SOURCE_PERIOD_PREVIOUS
            ? self::SOURCE_PERIOD_PREVIOUS
            : self::SOURCE_PERIOD_CURRENT;
    }

    private function sourcePeriodModeLabel(?string $mode): string
    {
        return $this->normalizeSourcePeriodMode($mode) === self::SOURCE_PERIOD_PREVIOUS
            ? 'Bulan Sebelumnya'
            : 'Periode Berjalan';
    }

    private function normalizeEcgDistributionMode(?string $mode): string
    {
        return $mode === self::ECG_DISTRIBUTION_FULL_AMOUNT
            ? self::ECG_DISTRIBUTION_FULL_AMOUNT
            : self::ECG_DISTRIBUTION_SPLIT_EVENLY;
    }

    private function ecgDistributionModeLabel(?string $mode): string
    {
        return $this->normalizeEcgDistributionMode($mode) === self::ECG_DISTRIBUTION_FULL_AMOUNT
            ? 'Diberikan penuh'
            : 'Dibagi rata';
    }

    private function cleanDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function cleanTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse((string) $value)->format('H:i:s');
    }
}
