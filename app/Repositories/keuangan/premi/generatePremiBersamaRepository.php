<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generatePremiBersamaDetailModel;
use App\Models\dbSimrs\generatePremiBersamaDistributionModel;
use App\Models\dbSimrs\generatePremiBersamaModel;
use App\Models\dbSimrs\generatePremiBersamaSourceModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class generatePremiBersamaRepository
{
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

    private const SOURCE_PATTERNS = [
        'rawat_*_pr' => [
            'label' => 'Rawat jalan & inap paramedis',
            'tables' => ['rawat_jl_pr', 'rawat_inap_pr', 'rawat_jl_drpr', 'rawat_inap_drpr'],
        ],
        'rawat_jl_pr' => [
            'label' => 'Rawat jalan paramedis',
            'tables' => ['rawat_jl_pr'],
        ],
        'rawat_inap_pr' => [
            'label' => 'Rawat inap paramedis',
            'tables' => ['rawat_inap_pr'],
        ],
        'rawat_*_dr' => [
            'label' => 'Rawat jalan & inap dokter',
            'tables' => ['rawat_jl_dr', 'rawat_inap_dr', 'rawat_jl_drpr', 'rawat_inap_drpr'],
        ],
        'rawat_jl_dr' => [
            'label' => 'Rawat jalan dokter',
            'tables' => ['rawat_jl_dr'],
        ],
        'rawat_inap_dr' => [
            'label' => 'Rawat inap dokter',
            'tables' => ['rawat_inap_dr'],
        ],
        'rawat_*_drpr' => [
            'label' => 'Rawat jalan & inap dokter-paramedis',
            'tables' => ['rawat_jl_drpr', 'rawat_inap_drpr'],
        ],
        'rawat_jl_drpr' => [
            'label' => 'Rawat jalan dokter-paramedis',
            'tables' => ['rawat_jl_drpr'],
        ],
        'rawat_inap_drpr' => [
            'label' => 'Rawat inap dokter-paramedis',
            'tables' => ['rawat_inap_drpr'],
        ],
    ];

    private const DOCTOR_SOURCE_TABLES = [
        'rawat_jl_dr',
        'rawat_inap_dr',
        'rawat_jl_drpr',
        'rawat_inap_drpr',
    ];

    private const DOCTOR_TO_PARAMEDIS_SOURCE_TABLE = [
        'rawat_jl_dr' => 'rawat_jl_pr',
        'rawat_inap_dr' => 'rawat_inap_pr',
        'rawat_jl_drpr' => 'rawat_jl_pr',
        'rawat_inap_drpr' => 'rawat_inap_pr',
    ];

    public function getResults(
        ?string $periode = null,
        ?string $jenisPelayanan = null,
        ?int $jnsPremiId = null
    ): Collection {
        return generatePremiBersamaModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name', 'jnsPremi:id,pembagi'])
            ->withCount(['sources', 'details', 'distributions'])
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisPelayanan, fn ($query) => $query->where('jenis_pelayanan', $jenisPelayanan))
            ->when($jnsPremiId, fn ($query) => $query->where('jnsPremi_id', $jnsPremiId))
            ->orderByDesc('periode')
            ->orderBy('jenis_pelayanan')
            ->orderBy('nama_premi')
            ->get();
    }

    public function getConfig(): object
    {
        $config = DB::table('generate_premi_bersama_configs')->first();

        if ($config) {
            return $config;
        }

        $defaultPremiId = DB::table('master_jenis_premi')
            ->where(function ($query) {
                $query
                    ->where('jenis', 'like', '%Bersama%')
                    ->orWhere('jenis', 'like', '%Premi Medis%')
                    ->orWhere('jenis', 'like', '%Tindakan Medis%');
            })
            ->orderByRaw("case when jenis like '%Bersama%' then 0 else 1 end")
            ->orderBy('jenis')
            ->value('id');
        $now = now();
        $id = DB::table('generate_premi_bersama_configs')->insertGetId([
            'jnsPremi_umum_id' => $defaultPremiId,
            'jnsPremi_bpjs_id' => $defaultPremiId,
            'bpjs_source_mode' => PremiSourcePeriod::MODE_PREVIOUS,
            'ignore_icu' => true,
            'ignore_nicu' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('generate_premi_bersama_configs')->where('id', $id)->first();
    }

    public function saveConfig(
        int $jnsPremiUmumId,
        ?int $jnsPremiBpjsId,
        ?int $ugdPlotingId,
        ?int $vkPlotingId,
        ?int $kamarPlotingId,
        ?int $bhpPlotingId,
        string $bpjsSourceMode,
        bool $ignoreIcu,
        bool $ignoreNicu,
        array $sourceMappings,
        array $includedUmumActionIds,
        array $doctorCodes,
        array $doctorActionIds
    ): object {
        return DB::transaction(function () use (
            $jnsPremiUmumId,
            $jnsPremiBpjsId,
            $ugdPlotingId,
            $vkPlotingId,
            $kamarPlotingId,
            $bhpPlotingId,
            $bpjsSourceMode,
            $ignoreIcu,
            $ignoreNicu,
            $sourceMappings,
            $includedUmumActionIds,
            $doctorCodes,
            $doctorActionIds
        ) {
            $config = $this->getConfig();
            $now = now();

            DB::table('generate_premi_bersama_configs')
                ->where('id', $config->id)
                ->update([
                    'jnsPremi_umum_id' => $jnsPremiUmumId,
                    'jnsPremi_bpjs_id' => $jnsPremiBpjsId ?: $jnsPremiUmumId,
                    'ugd_plotingPremi_id' => $ugdPlotingId,
                    'vk_plotingPremi_id' => $vkPlotingId,
                    'kamar_plotingPremi_id' => $kamarPlotingId,
                    'bhp_plotingPremi_id' => $bhpPlotingId,
                    'bpjs_source_mode' => PremiSourcePeriod::normalizeMode($bpjsSourceMode, 'bpjs'),
                    'ignore_icu' => $ignoreIcu,
                    'ignore_nicu' => $ignoreNicu,
                    'updated_at' => $now,
                ]);

            DB::table('generate_premi_bersama_config_source')
                ->where('config_id', $config->id)
                ->delete();

            $sourceRows = collect($sourceMappings)
                ->map(fn (array $item) => [
                    'config_id' => $config->id,
                    'source_pattern' => (string) $item['source_pattern'],
                    'jnsTindakan_id' => (int) $item['jnsTindakan_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->unique(fn (array $item) => $item['source_pattern'].'|'.$item['jnsTindakan_id'])
                ->values();

            if ($sourceRows->isNotEmpty()) {
                DB::table('generate_premi_bersama_config_source')->insert($sourceRows->all());
            }

            $this->replaceIncludedActions((int) $config->id, 'umum', $includedUmumActionIds);
            $this->replaceDoctorConfig((int) $config->id, $doctorCodes);
            $this->replaceDoctorActionConfig((int) $config->id, $doctorActionIds);

            return DB::table('generate_premi_bersama_configs')
                ->where('id', $config->id)
                ->first();
        });
    }

    public function sourcePatternOptions(): Collection
    {
        return collect(self::SOURCE_PATTERNS)
            ->map(fn (array $definition, string $key) => [
                'id' => $key,
                'source_pattern' => $key,
                'label' => $definition['label'],
                'tables' => $definition['tables'],
            ])
            ->values();
    }

    public function getMappingPremiOptions(): Collection
    {
        return DB::table('master_jenis_premi as jp')
            ->join('mapping_premi as mp', 'mp.jnsPremi_id', '=', 'jp.id')
            ->leftJoin('mapping_premi_pegawai as mpp', 'mpp.jnsPremi_id', '=', 'jp.id')
            ->select([
                'jp.id',
                'jp.kode',
                'jp.jenis',
                'jp.pembagi',
                DB::raw('COUNT(DISTINCT mp.id) as jumlah_tindakan'),
                DB::raw('COUNT(DISTINCT mpp.id) as jumlah_pegawai'),
            ])
            ->groupBy('jp.id', 'jp.kode', 'jp.jenis', 'jp.pembagi')
            ->orderBy('jp.jenis')
            ->get();
    }

    public function getPremiActionOptions(int $jnsPremiId): Collection
    {
        return DB::table('mapping_premi as mp')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mp.jnsTindakan_id')
            ->leftJoin('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'jt.id')
            ->select([
                'jt.id',
                'jt.kode',
                'jt.jenis',
                'mp.id as mapping_premi_id',
                'mp.jenis_umum',
                'mp.jenis_bpjs',
                'mp.nilai_umum',
                'mp.nilai_bpjs',
                'mp.nilai_bersama_umum',
                'mp.nilai_bersama_bpjs',
                DB::raw('COUNT(DISTINCT mt.id) as jumlah_mapping_tindakan'),
            ])
            ->where('mp.jnsPremi_id', $jnsPremiId)
            ->groupBy(
                'jt.id',
                'jt.kode',
                'jt.jenis',
                'mp.id',
                'mp.jenis_umum',
                'mp.jenis_bpjs',
                'mp.nilai_umum',
                'mp.nilai_bpjs',
                'mp.nilai_bersama_umum',
                'mp.nilai_bersama_bpjs'
            )
            ->orderBy('jt.jenis')
            ->get();
    }

    public function findPremi(int $jnsPremiId): ?object
    {
        return DB::table('master_jenis_premi')
            ->select('id', 'kode', 'jenis', 'pembagi')
            ->where('id', $jnsPremiId)
            ->first();
    }

    public function getPlotingOptions(): Collection
    {
        return DB::table('master_ploting_premi')
            ->select('id', 'kode', 'ploting')
            ->orderBy('ploting')
            ->get();
    }

    public function findPloting(?int $plotingId): ?object
    {
        if (! $plotingId) {
            return null;
        }

        return DB::table('master_ploting_premi')
            ->select('id', 'kode', 'ploting')
            ->where('id', $plotingId)
            ->first();
    }

    public function getDokterOptions(?string $keyword = null): Collection
    {
        return DB::connection('mysql_khanza')
            ->table('dokter')
            ->select('kd_dokter', 'nm_dokter')
            ->whereNotIn('kd_dokter', ['-', ''])
            ->whereNotIn('nm_dokter', ['-', ''])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('kd_dokter', 'like', "%{$keyword}%")
                        ->orWhere('nm_dokter', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('nm_dokter')
            ->limit(50)
            ->get();
    }

    public function findDoctors(Collection $doctorCodes): Collection
    {
        $doctorCodes = $doctorCodes
            ->filter()
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();

        if ($doctorCodes->isEmpty()) {
            return collect();
        }

        return DB::connection('mysql_khanza')
            ->table('dokter')
            ->select('kd_dokter', 'nm_dokter')
            ->whereIn('kd_dokter', $doctorCodes->all())
            ->whereNotIn('kd_dokter', ['-', ''])
            ->whereNotIn('nm_dokter', ['-', ''])
            ->orderBy('nm_dokter')
            ->get();
    }

    public function getConfigSourceMappings(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_bersama_config_source as cs')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'cs.jnsTindakan_id')
            ->select([
                'cs.id',
                'cs.source_pattern',
                'cs.jnsTindakan_id',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->where('cs.config_id', $configId)
            ->orderBy('cs.source_pattern')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getSelectedDoctors(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_bersama_config_doctor')
            ->select('kd_dokter', 'nm_dokter')
            ->where('config_id', $configId)
            ->orderBy('nm_dokter')
            ->get();
    }

    public function getSelectedDoctorActionIds(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_bersama_config_doctor_action')
            ->where('config_id', $configId)
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function getSelectedDoctorActions(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_bersama_config_doctor_action as da')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'da.jnsTindakan_id')
            ->select(['jt.id', 'jt.kode', 'jt.jenis'])
            ->where('da.config_id', $configId)
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getIncludedActionIds(?int $configId = null, string $jenisPelayanan = 'umum'): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_bersama_config_include_action')
            ->where('config_id', $configId)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function getIncludedActions(?int $configId = null, string $jenisPelayanan = 'umum'): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_bersama_config_include_action as ia')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'ia.jnsTindakan_id')
            ->select(['jt.id', 'jt.kode', 'jt.jenis'])
            ->where('ia.config_id', $configId)
            ->where('ia.jenis_pelayanan', $jenisPelayanan)
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getMappedPegawaiWithScores(int $jnsPremiId): Collection
    {
        $pegawai = DB::table('mapping_premi_pegawai as mpp')
            ->leftJoin('gaji_pokok as gp', 'gp.nik', '=', 'mpp.nik')
            ->select([
                'mpp.nik',
                DB::raw('COALESCE(gp.nama, mpp.nik) as pegawai_name'),
                'gp.jbtn as pegawai_position',
                'gp.stts_kerja',
            ])
            ->where('mpp.jnsPremi_id', $jnsPremiId)
            ->orderBy('pegawai_name')
            ->get();

        $niks = $pegawai->pluck('nik')->map(fn ($nik) => (string) $nik)->unique()->values();

        if ($niks->isEmpty()) {
            return collect();
        }

        $scoreSelect = [
            'sp.nik',
            'sp.skor_id',
            'sp.bobot_skor',
            'ms.jenis',
            'ms.keterangan',
        ];

        if (Schema::hasColumn('master_skor', 'kd_skor')) {
            $scoreSelect[] = 'ms.kd_skor';
        }

        $scores = DB::table('skoring_pegawai as sp')
            ->leftJoin('master_skor as ms', 'ms.id', '=', 'sp.skor_id')
            ->select($scoreSelect)
            ->whereIn('sp.nik', $niks->all())
            ->when(
                Schema::hasColumn('skoring_pegawai', 'deleted_at'),
                fn ($query) => $query->whereNull('sp.deleted_at')
            )
            ->get()
            ->groupBy(fn ($row) => (string) $row->nik);

        return $pegawai
            ->map(function ($item) use ($scores) {
                $scoreRows = collect($scores->get((string) $item->nik, collect()));

                return (object) [
                    'nik' => (string) $item->nik,
                    'pegawai_name' => $item->pegawai_name,
                    'pegawai_position' => $item->pegawai_position,
                    'stts_kerja' => $item->stts_kerja,
                    'skor_pegawai' => round((float) $scoreRows->sum('bobot_skor'), 2),
                    'jumlah_skor' => $scoreRows->count(),
                    'skor_detail' => $scoreRows
                        ->map(fn ($score) => [
                            'skor_id' => (int) $score->skor_id,
                            'kd_skor' => $score->kd_skor ?? null,
                            'jenis' => $score->jenis,
                            'keterangan' => $score->keterangan,
                            'bobot_skor' => round((float) $score->bobot_skor, 2),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();
    }

    public function calculate(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId,
        object $config,
        Collection $sourceMappings,
        bool $forUpdate = false
    ): array {
        $selectedPremi = $this->findPremi($jnsPremiId);
        $bpjsSourceMode = PremiSourcePeriod::normalizeMode($config->bpjs_source_mode ?? null, 'bpjs');
        $range = $jenisPelayanan === 'bpjs'
            ? PremiSourcePeriod::range($periode, 'bpjs', $bpjsSourceMode)
            : $this->periodRange($periode);
        $sourcePeriode = $jenisPelayanan === 'bpjs'
            ? PremiSourcePeriod::resolve($periode, 'bpjs', $bpjsSourceMode)
            : $periode;
        $bpjsSourcePeriode = PremiSourcePeriod::resolve($periode, 'bpjs', $bpjsSourceMode);
        $bpjsRange = PremiSourcePeriod::range($periode, 'bpjs', $bpjsSourceMode);
        $karcisBpjsActionIds = $this->getIncludedActionIds((int) $config->id, 'umum');
        $mappings = $this->getCalculationMappings(
            $jenisPelayanan,
            $jnsPremiId,
            $karcisBpjsActionIds
        );
        $karcisMappings = collect();

        if ($jenisPelayanan === 'umum' && $karcisBpjsActionIds->isNotEmpty()) {
            $karcisMappings = $this->getCalculationMappings(
                'umum',
                $jnsPremiId,
                collect(),
                $karcisBpjsActionIds
            );
            $mappedKarcisActionIds = $karcisMappings
                ->pluck('jnsTindakan_id')
                ->map(fn ($id) => (int) $id)
                ->unique();
            $missingKarcisActionIds = $karcisBpjsActionIds
                ->reject(fn ($id) => $mappedKarcisActionIds->contains((int) $id))
                ->values();
            $jnsPremiBpjsId = (int) ($config->jnsPremi_bpjs_id ?? 0);

            if (
                $missingKarcisActionIds->isNotEmpty()
                && $jnsPremiBpjsId > 0
                && $jnsPremiBpjsId !== $jnsPremiId
            ) {
                $karcisMappings = $karcisMappings->merge(
                    $this->getCalculationMappings(
                        'umum',
                        $jnsPremiBpjsId,
                        collect(),
                        $missingKarcisActionIds
                    )
                );
            }
        }
        $doctorCodes = $this->getSelectedDoctors((int) $config->id)
            ->pluck('kd_dokter')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();
        $doctorActionIds = $this->getSelectedDoctorActionIds((int) $config->id);
        $generatorSources = $this->getGeneratorSources($periode, $jenisPelayanan, $config, $forUpdate);

        if ($mappings->isEmpty() && $karcisMappings->isEmpty()) {
            return [
                'transactions' => collect(),
                'ignored' => collect(),
                'details' => collect(),
                'generator_sources' => $generatorSources,
                'source_periode' => $sourcePeriode,
                'bpjs_source_mode' => $bpjsSourceMode,
                'bpjs_source_periode' => $bpjsSourcePeriode,
                'source_tgl_awal' => $range['start']->toDateString(),
                'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
                'jnsPremi_id' => $jnsPremiId,
                'kode_premi' => $selectedPremi?->kode,
                'nama_premi' => $selectedPremi?->jenis,
                'jumlah_transaksi' => 0,
                'jumlah_pasien' => 0,
                'jumlah_jenis_tindakan' => 0,
                'jumlah_mapping_premi' => 0,
                'jumlah_terabaikan_icu' => 0,
                'jumlah_terabaikan_nicu' => 0,
                'total_biaya_rawat' => 0,
                'total_tindakan_rawat' => 0,
                ...$this->generatorSummary($generatorSources),
            ];
        }

        $transactionsPayload = $this->collectTransactions(
            $jenisPelayanan,
            $range['start'],
            $range['end'],
            $mappings,
            $sourceMappings,
            (bool) ($config->ignore_icu ?? true),
            (bool) ($config->ignore_nicu ?? true),
            $jenisPelayanan,
            $doctorCodes,
            $doctorActionIds
        );
        $ignored = $transactionsPayload['ignored'];

        if ($karcisMappings->isNotEmpty()) {
            $karcisPayload = $this->collectTransactions(
                'bpjs',
                $bpjsRange['start'],
                $bpjsRange['end'],
                $karcisMappings,
                $sourceMappings,
                (bool) ($config->ignore_icu ?? true),
                (bool) ($config->ignore_nicu ?? true),
                'bpjs_karcis',
                $doctorCodes,
                $doctorActionIds
            );
            $transactionsPayload['transactions'] = $transactionsPayload['transactions']
                ->merge($karcisPayload['transactions']);
            $ignored = $ignored->merge($karcisPayload['ignored']);
        }

        $transactions = $transactionsPayload['transactions'];
        $normalTransactionsByAction = $transactions
            ->reject(fn (array $item) => ($item['jenis_pelayanan_sumber'] ?? '') === 'bpjs_karcis')
            ->groupBy('jnsTindakan_id');
        $karcisTransactionsByAction = $transactions
            ->filter(fn (array $item) => ($item['jenis_pelayanan_sumber'] ?? '') === 'bpjs_karcis')
            ->groupBy('jnsTindakan_id');
        $details = $mappings
            ->unique('mapping_premi_id')
            ->map(function ($mapping) use ($normalTransactionsByAction, $jenisPelayanan, $sourceMappings) {
                $items = $normalTransactionsByAction
                    ->get((int) $mapping->jnsTindakan_id, collect())
                    ->values();

                return $this->makeDetail($mapping, $items, $jenisPelayanan, $sourceMappings);
            })
            ->merge(
                $karcisMappings
                    ->unique('mapping_premi_id')
                    ->map(function ($mapping) use ($karcisTransactionsByAction, $sourceMappings) {
                        $items = $karcisTransactionsByAction
                            ->get((int) $mapping->jnsTindakan_id, collect())
                            ->values();

                        return $this->makeDetail(
                            $mapping,
                            $items,
                            'umum',
                            $sourceMappings,
                            ' (Karcis BPJS)'
                        );
                    })
            )
            ->filter(fn (array $detail) => $detail['jumlah_data'] > 0)
            ->sortBy('nama_jenis_tindakan')
            ->values();

        return [
            'transactions' => $transactions,
            'ignored' => $ignored,
            'details' => $details,
            'generator_sources' => $generatorSources,
            'source_periode' => $sourcePeriode,
            'bpjs_source_mode' => $bpjsSourceMode,
            'bpjs_source_periode' => $bpjsSourcePeriode,
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jnsPremi_id' => $jnsPremiId,
            'kode_premi' => $selectedPremi?->kode,
            'nama_premi' => $selectedPremi?->jenis,
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_jenis_tindakan' => $transactions->pluck('jnsTindakan_id')->unique()->count(),
            'jumlah_mapping_premi' => $details->count(),
            'jumlah_terabaikan_icu' => $ignored
                ->where('is_icu', true)
                ->count(),
            'jumlah_terabaikan_nicu' => $ignored
                ->where('is_nicu', true)
                ->count(),
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat'), 2),
            'total_tindakan_rawat' => round((float) $details->sum('hasil_mapping'), 2),
            ...$this->generatorSummary($generatorSources),
        ];
    }

    public function getGeneratorSources(
        string $periode,
        string $jenisPelayanan,
        object $config,
        bool $forUpdate = false
    ): Collection {
        if ($jenisPelayanan !== 'umum') {
            return collect();
        }

        return collect([
            $this->plotingGeneratorSource(
                'ugd',
                'UGD',
                'generate_ugd',
                'jenis_ugd',
                'jumlah_pasien',
                'total_ugd',
                $periode,
                $jenisPelayanan,
                $config->ugd_plotingPremi_id ?? null,
                $forUpdate
            ),
            $this->plainGeneratorSource(
                'laboratorium',
                'Laboratorium',
                'generate_laboratorium',
                'jenis_laboratorium',
                'jumlah_tindakan',
                'total_premi_bersama',
                $periode,
                $jenisPelayanan,
                $forUpdate
            ),
            $this->plainGeneratorSource(
                'radiologi',
                'Radiologi',
                'generate_radiologi',
                'jenis_radiologi',
                'jumlah_tindakan',
                'total_premi_bersama',
                $periode,
                $jenisPelayanan,
                $forUpdate
            ),
            $this->apotekGeneratorSource($periode, $jenisPelayanan, $forUpdate),
            $this->plotingGeneratorSource(
                'vk',
                'VK',
                'generate_vk',
                'jenis_vk',
                'jumlah_tindakan',
                'total_vk',
                $periode,
                $jenisPelayanan,
                $config->vk_plotingPremi_id ?? null,
                $forUpdate
            ),
            $this->plotingGeneratorSource(
                'kamar',
                'Kamar',
                'generate_kamar_inap',
                'jenis_kamar',
                'jumlah_lama_inap',
                'total_lama_inap',
                $periode,
                $jenisPelayanan,
                $config->kamar_plotingPremi_id ?? null,
                $forUpdate
            ),
            $this->plotingGeneratorSource(
                'bhp',
                'BHP',
                'generate_bhp',
                'jenis_bhp',
                'jumlah_bhp',
                'total_bhp',
                $periode,
                $jenisPelayanan,
                $config->bhp_plotingPremi_id ?? null,
                $forUpdate
            ),
            $this->plainGeneratorSource(
                'gizi',
                'Gizi',
                'generate_gizi',
                'jenis_gizi',
                'jumlah_tindakan',
                'total_premi_bersama',
                $periode,
                $jenisPelayanan,
                $forUpdate
            ),
            $this->plainGeneratorSource(
                'nicu',
                'NICU',
                'generate_nicu',
                'jenis_nicu',
                'jumlah_tindakan',
                'total_premi_bersama',
                $periode,
                $jenisPelayanan,
                $forUpdate
            ),
            $this->plainGeneratorSource(
                'operasi',
                'Operasi',
                'generate_operasi',
                'jenis_operasi',
                'total_operasi',
                'total_premi_bersama',
                $periode,
                $jenisPelayanan,
                $forUpdate
            ),
        ])->values();
    }

    public function findByPeriodAndType(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId
    ): ?generatePremiBersamaModel {
        return generatePremiBersamaModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name', 'jnsPremi:id,pembagi'])
            ->with([
                'sources' => fn ($query) => $query->orderBy('source_key'),
                'details' => fn ($query) => $query->orderBy('nama_jenis_tindakan'),
                'distributions' => fn ($query) => $query->orderByDesc('total_received'),
            ])
            ->where('periode', $periode)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->where('jnsPremi_id', $jnsPremiId)
            ->first();
    }

    public function findByPeriodAndTypeForUpdate(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId
    ): ?generatePremiBersamaModel {
        return generatePremiBersamaModel::query()
            ->where('periode', $periode)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->where('jnsPremi_id', $jnsPremiId)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId,
        object $config,
        array $calculation,
        Collection $sourceMappings
    ): generatePremiBersamaModel {
        $grandTotal = round(
            (float) $calculation['total_tindakan_rawat']
            + (float) $calculation['total_generator_sumber'],
            2
        );
        $ugdPloting = $this->findPloting($config->ugd_plotingPremi_id ?? null);
        $vkPloting = $this->findPloting($config->vk_plotingPremi_id ?? null);
        $kamarPloting = $this->findPloting($config->kamar_plotingPremi_id ?? null);
        $bhpPloting = $this->findPloting($config->bhp_plotingPremi_id ?? null);

        return generatePremiBersamaModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_pelayanan' => $jenisPelayanan,
                'jnsPremi_id' => $jnsPremiId,
            ],
            [
                'source_periode' => $calculation['source_periode'],
                'bpjs_source_mode' => $calculation['bpjs_source_mode'],
                'bpjs_source_periode' => $calculation['bpjs_source_periode'],
                'source_tgl_awal' => $calculation['source_tgl_awal'],
                'source_tgl_akhir' => $calculation['source_tgl_akhir'],
                'kode_premi' => $calculation['kode_premi'],
                'nama_premi' => $calculation['nama_premi'],
                'ugd_plotingPremi_id' => $ugdPloting?->id,
                'ugd_kode_ploting' => $ugdPloting?->kode,
                'ugd_nama_ploting' => $ugdPloting?->ploting,
                'vk_plotingPremi_id' => $vkPloting?->id,
                'vk_kode_ploting' => $vkPloting?->kode,
                'vk_nama_ploting' => $vkPloting?->ploting,
                'kamar_plotingPremi_id' => $kamarPloting?->id,
                'kamar_kode_ploting' => $kamarPloting?->kode,
                'kamar_nama_ploting' => $kamarPloting?->ploting,
                'bhp_plotingPremi_id' => $bhpPloting?->id,
                'bhp_kode_ploting' => $bhpPloting?->kode,
                'bhp_nama_ploting' => $bhpPloting?->ploting,
                'ignore_icu' => (bool) ($config->ignore_icu ?? true),
                'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
                'jumlah_transaksi' => $calculation['jumlah_transaksi'],
                'jumlah_pasien' => $calculation['jumlah_pasien'],
                'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan'],
                'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi'],
                'jumlah_terabaikan_icu' => $calculation['jumlah_terabaikan_icu'],
                'jumlah_terabaikan_nicu' => $calculation['jumlah_terabaikan_nicu'],
                'total_biaya_rawat' => $calculation['total_biaya_rawat'],
                'total_tindakan_rawat' => $calculation['total_tindakan_rawat'],
                'jumlah_sumber_generator' => $calculation['jumlah_sumber_generator'],
                'jumlah_sumber_terkunci' => $calculation['jumlah_sumber_terkunci'],
                'total_generator_sumber' => $calculation['total_generator_sumber'],
                'grand_total' => $grandTotal,
                'jumlah_penerima' => 0,
                'total_skor' => 0,
                'total_dibagikan' => 0,
                'config_snapshot' => [
                    'bpjs_source_mode' => $calculation['bpjs_source_mode'],
                    'bpjs_source_periode' => $calculation['bpjs_source_periode'],
                    'ignore_icu' => (bool) ($config->ignore_icu ?? true),
                    'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
                    'source_mappings' => $this->sourceMappingsPayload($sourceMappings),
                    'included_umum_action_ids' => $this->getIncludedActionIds((int) $config->id, 'umum')
                        ->values()
                        ->all(),
                    'doctor_codes' => $this->getSelectedDoctors((int) $config->id)
                        ->pluck('kd_dokter')
                        ->values()
                        ->all(),
                    'doctor_tindakan_ids' => $this->getSelectedDoctorActionIds((int) $config->id)
                        ->values()
                        ->all(),
                ],
                'generate_by' => Auth::id(),
            ]
        );
    }

    public function replaceSources(generatePremiBersamaModel $header, Collection $sources): void
    {
        $header->sources()->delete();

        if ($sources->isEmpty()) {
            return;
        }

        $now = now();
        $sources
            ->map(fn (array $source) => [
                ...$source,
                'generate_premi_bersama_id' => $header->id,
                'raw_snapshot' => json_encode(
                    $source['raw_snapshot'] ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(100)
            ->each(fn (Collection $chunk) => generatePremiBersamaSourceModel::query()
                ->insert($chunk->all()));
    }

    public function replaceDetails(generatePremiBersamaModel $header, Collection $details): void
    {
        $header->details()->delete();

        if ($details->isEmpty()) {
            return;
        }

        $now = now();
        $details
            ->map(fn (array $detail) => [
                ...$detail,
                'generate_premi_bersama_id' => $header->id,
                'source_rules' => json_encode(
                    $detail['source_rules'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'mapping_snapshot' => json_encode(
                    $detail['mapping_snapshot'] ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'data_rawat' => json_encode(
                    $detail['data_rawat'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(100)
            ->each(fn (Collection $chunk) => generatePremiBersamaDetailModel::query()
                ->insert($chunk->all()));
    }

    public function replaceDistributions(
        generatePremiBersamaModel $header,
        Collection $distributions
    ): void {
        $header->distributions()->delete();

        $summary = [
            'jumlah_penerima' => $distributions->count(),
            'total_skor' => round((float) data_get($distributions->first(), 'total_skor', 0), 2),
            'total_dibagikan' => round((float) $distributions->sum('total_received'), 2),
            'updated_at' => now(),
        ];

        if ($distributions->isNotEmpty()) {
            $now = now();
            $distributions
                ->map(fn (array $distribution) => [
                    'generate_premi_bersama_id' => $header->id,
                    'jnsPremi_id' => $distribution['jnsPremi_id'],
                    'nik' => $distribution['nik'],
                    'pegawai_name' => $distribution['pegawai_name'],
                    'pegawai_position' => $distribution['pegawai_position'],
                    'stts_kerja' => $distribution['stts_kerja'],
                    'skor_pegawai' => $distribution['skor_pegawai'],
                    'total_skor' => $distribution['total_skor'],
                    'allocation_percent' => $distribution['allocation_percent'],
                    'grand_total' => $distribution['grand_total'],
                    'total_received' => $distribution['total_received'],
                    'skor_detail' => json_encode(
                        $distribution['skor_detail'] ?? [],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->chunk(100)
                ->each(fn (Collection $chunk) => generatePremiBersamaDistributionModel::query()
                    ->insert($chunk->all()));
        }

        DB::table('generate_premi_bersama')
            ->where('id', $header->id)
            ->update($summary);
    }

    public function findWithDetails(int $id): ?generatePremiBersamaModel
    {
        return generatePremiBersamaModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name', 'jnsPremi:id,pembagi'])
            ->with([
                'sources' => fn ($query) => $query->orderBy('source_key'),
                'details' => fn ($query) => $query->orderBy('nama_jenis_tindakan'),
                'distributions' => fn ($query) => $query->orderByDesc('total_received'),
            ])
            ->find($id);
    }

    public function findForUpdate(int $id): ?generatePremiBersamaModel
    {
        return generatePremiBersamaModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function updateLock(
        generatePremiBersamaModel $header,
        bool $isLocked,
        ?int $userId = null
    ): generatePremiBersamaModel {
        DB::table('generate_premi_bersama')
            ->where('id', $header->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generatePremiBersamaModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($header->id);
    }

    private function replaceIncludedActions(
        int $configId,
        string $jenisPelayanan,
        array $tindakanIds
    ): void {
        DB::table('generate_premi_bersama_config_include_action')
            ->where('config_id', $configId)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->delete();

        if (empty($tindakanIds)) {
            return;
        }

        $now = now();
        DB::table('generate_premi_bersama_config_include_action')->insert(
            collect($tindakanIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->map(fn ($id) => [
                    'config_id' => $configId,
                    'jenis_pelayanan' => $jenisPelayanan,
                    'jnsTindakan_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all()
        );
    }

    private function replaceDoctorConfig(int $configId, array $doctorCodes): void
    {
        DB::table('generate_premi_bersama_config_doctor')
            ->where('config_id', $configId)
            ->delete();

        $doctors = $this->findDoctors(
            collect($doctorCodes)
                ->map(fn ($code) => (string) $code)
                ->unique()
                ->values()
        );

        if ($doctors->isEmpty()) {
            return;
        }

        $now = now();
        DB::table('generate_premi_bersama_config_doctor')->insert(
            $doctors
                ->map(fn ($doctor) => [
                    'config_id' => $configId,
                    'kd_dokter' => $doctor->kd_dokter,
                    'nm_dokter' => $doctor->nm_dokter,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all()
        );
    }

    private function replaceDoctorActionConfig(int $configId, array $tindakanIds): void
    {
        DB::table('generate_premi_bersama_config_doctor_action')
            ->where('config_id', $configId)
            ->delete();

        if (empty($tindakanIds)) {
            return;
        }

        $now = now();
        DB::table('generate_premi_bersama_config_doctor_action')->insert(
            collect($tindakanIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->map(fn ($id) => [
                    'config_id' => $configId,
                    'jnsTindakan_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all()
        );
    }

    private function getCalculationMappings(
        string $jenisPelayanan,
        int $jnsPremiId,
        Collection $karcisBpjsActionIds,
        ?Collection $onlyActionIds = null
    ): Collection {
        $valueColumn = $jenisPelayanan === 'bpjs'
            ? 'mp.nilai_bersama_bpjs'
            : 'mp.nilai_bersama_umum';
        $jenisColumn = $jenisPelayanan === 'bpjs'
            ? 'mp.jenis_bpjs'
            : 'mp.jenis_umum';

        return DB::table('mapping_tindakan as mt')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'mt.jnsTindakan_id')
            ->join('mapping_premi as mp', 'mp.jnsTindakan_id', '=', 'mt.jnsTindakan_id')
            ->join('master_jenis_premi as jp', 'jp.id', '=', 'mp.jnsPremi_id')
            ->select([
                'mt.id as mapping_tindakan_id',
                'mt.sumber_tindakan',
                'mt.kd_tindakan',
                'mt.nm_tindakan',
                'mt.jnsTindakan_id',
                'mp.id as mapping_premi_id',
                'mp.jnsPremi_id',
                'mp.jenis_umum',
                'mp.jenis_bpjs',
                'mp.nilai_bersama_umum',
                'mp.nilai_bersama_bpjs',
                DB::raw("{$jenisColumn} as jenis_mapping"),
                DB::raw("{$valueColumn} as nilai_mapping"),
                'jp.kode as kode_premi',
                'jp.jenis as nama_premi',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->where('mp.jnsPremi_id', $jnsPremiId)
            ->when(
                $jenisPelayanan === 'bpjs' && $karcisBpjsActionIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('mp.jnsTindakan_id', $karcisBpjsActionIds->all())
            )
            ->when(
                $onlyActionIds?->isNotEmpty(),
                fn ($query) => $query->whereIn('mp.jnsTindakan_id', $onlyActionIds->all())
            )
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.kd_tindakan')
            ->get();
    }

    private function collectTransactions(
        string $jenisPelayanan,
        Carbon $start,
        Carbon $end,
        Collection $mappings,
        Collection $sourceMappings,
        bool $ignoreIcu,
        bool $ignoreNicu,
        string $sourceType,
        Collection $doctorCodes,
        Collection $doctorActionIds
    ): array {
        $mappingBySource = $mappings
            ->groupBy(fn ($mapping) => $mapping->sumber_tindakan.'|'.$mapping->kd_tindakan);
        $codesBySource = $mappings
            ->groupBy('sumber_tindakan')
            ->map(fn (Collection $items) => $items->pluck('kd_tindakan')->unique()->values());
        $allowedTablesByAction = $this->allowedTablesByAction($sourceMappings);
        $rawRows = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            $codes = $codesBySource->get($definition['source'], collect());

            if ($codes->isEmpty()) {
                continue;
            }

            $rawRows = $rawRows->merge(
                $this->sourceQuery($definition, $jenisPelayanan, $start, $end, $codes)->get()
            );
        }

        $candidateRows = collect();

        foreach ($rawRows as $row) {
            $key = $row->sumber_tindakan.'|'.$row->kd_tindakan;
            $actionMappings = $mappingBySource->get($key, collect())
                ->unique('jnsTindakan_id');

            if ($actionMappings->isEmpty()) {
                continue;
            }

            $hasFilteredDoctorAction = $this->rowHasFilteredDoctorAction(
                $row,
                $actionMappings,
                $sourceType,
                $doctorCodes,
                $doctorActionIds,
                $allowedTablesByAction
            );
            $doctorMatchesFilter = $doctorCodes->contains((string) $row->kd_dokter);

            foreach ($actionMappings as $mapping) {
                $routeInfo = $this->doctorRouteInfoForMappedRow(
                    $row,
                    (int) $mapping->jnsTindakan_id,
                    $sourceType,
                    $doctorCodes,
                    $doctorActionIds,
                    $hasFilteredDoctorAction,
                    $doctorMatchesFilter
                );
                $sourceTableForRule = $routeInfo['effective_source_table'] ?? $row->source_table;

                if (! $this->sourceAllowedForAction(
                    (int) $mapping->jnsTindakan_id,
                    $sourceTableForRule,
                    $allowedTablesByAction
                )) {
                    continue;
                }

                if (! $this->doctorAllowedForMappedRow(
                    $row,
                    (int) $mapping->jnsTindakan_id,
                    $sourceType,
                    $doctorCodes,
                    $doctorActionIds
                )) {
                    continue;
                }

                $candidateRows->push([
                    'row' => $row,
                    'mapping' => $mapping,
                    'route_info' => $routeInfo,
                ]);
            }
        }

        $noRawats = $candidateRows
            ->map(fn (array $candidate) => $candidate['row']->no_rawat)
            ->filter()
            ->unique()
            ->values();
        $icuRanges = $ignoreIcu && $noRawats->isNotEmpty()
            ? $this->icuRanges($noRawats)
            : collect();
        $nicuRanges = $ignoreNicu && $noRawats->isNotEmpty()
            ? $this->nicuRanges($noRawats)
            : collect();
        $transactions = collect();
        $ignored = collect();

        foreach ($candidateRows as $candidate) {
            $row = $candidate['row'];
            $mapping = $candidate['mapping'];
            $routeInfo = $candidate['route_info'] ?? [];
            $rowIcuRange = $ignoreIcu
                ? $this->matchingRoomRange($row, $icuRanges->get($row->no_rawat, collect()))
                : null;
            $rowNicuRange = $ignoreNicu
                ? $this->matchingRoomRange($row, $nicuRanges->get($row->no_rawat, collect()))
                : null;

            if ($rowIcuRange || $rowNicuRange) {
                $ignored->push($this->rawatPayload(
                    $row,
                    $mapping,
                    $rowIcuRange,
                    $rowNicuRange,
                    $sourceType,
                    $routeInfo
                ));

                continue;
            }

            $transactions->push($this->rawatPayload(
                $row,
                $mapping,
                null,
                null,
                $sourceType,
                $routeInfo
            ));
        }

        $unique = fn (array $row) => implode('|', [
            $row['jenis_pelayanan_sumber'] ?? '',
            $row['source_table'],
            $row['no_rawat'],
            $row['tanggal'],
            $row['jam'],
            $row['kd_tindakan'],
            $row['jnsTindakan_id'],
            $row['kd_dokter'] ?? '',
            $row['nip'] ?? '',
            $row['biaya_rawat'],
        ]);

        return [
            'transactions' => $transactions
                ->unique($unique)
                ->sortBy(fn (array $row) => implode('|', [
                    $row['tanggal'],
                    $row['jam'] ?? '',
                    $row['no_rawat'],
                    $row['source_table'],
                    $row['kd_tindakan'],
                    $row['jnsTindakan_id'],
                ]))
                ->values(),
            'ignored' => $ignored
                ->unique($unique)
                ->values(),
        ];
    }

    private function sourceQuery(
        array $definition,
        string $jenisPelayanan,
        Carbon $start,
        Carbon $end,
        Collection $codes
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
                'r.biaya_rawat',
            ])
            ->where('r.tgl_perawatan', '>=', $start->toDateString())
            ->where('r.tgl_perawatan', '<', $end->toDateString())
            ->whereIn('r.kd_jenis_prw', $codes->values()->all())
            ->where('r.biaya_rawat', '>', 0);

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

        return $jenisPelayanan === 'bpjs'
            ? $this->applyBpjsFilter($query)
            : $this->applyUmumFilter($query);
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

    private function makeDetail(
        object $mapping,
        Collection $items,
        string $jenisPelayanan,
        Collection $sourceMappings,
        string $nameSuffix = ''
    ): array {
        $nilaiMapping = (float) $mapping->nilai_mapping;
        $totalBiaya = round((float) $items->sum('biaya_rawat'), 2);
        $jumlahData = $items->count();
        $jenisMapping = $mapping->jenis_mapping;
        $dasarHitung = $jenisMapping === 'persen'
            ? $totalBiaya
            : $jumlahData;
        $hasil = $jenisMapping === 'persen'
            ? round($totalBiaya * ($nilaiMapping / 100), 2)
            : round($jumlahData * $nilaiMapping, 2);
        $sourceRules = $this->sourceRulesForAction((int) $mapping->jnsTindakan_id, $sourceMappings);

        return [
            'mapping_premi_id' => (int) $mapping->mapping_premi_id,
            'jnsPremi_id' => (int) $mapping->jnsPremi_id,
            'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
            'kode_premi' => $mapping->kode_premi,
            'nama_premi' => $mapping->nama_premi,
            'kode_jenis_tindakan' => $mapping->kode_jenis_tindakan,
            'nama_jenis_tindakan' => $mapping->nama_jenis_tindakan.$nameSuffix,
            'jenis_mapping' => $jenisMapping,
            'nilai_mapping' => $nilaiMapping,
            'source_rules' => $sourceRules,
            'mapping_snapshot' => $this->mappingSnapshot(
                $mapping,
                $items,
                $jenisPelayanan,
                $sourceRules,
                $nameSuffix,
                $totalBiaya,
                $jumlahData,
                $jenisMapping,
                $nilaiMapping,
                $dasarHitung,
                $hasil
            ),
            'jumlah_data' => $jumlahData,
            'jumlah_data_icu' => $items->where('is_icu', true)->count(),
            'jumlah_data_nicu' => $items->where('is_nicu', true)->count(),
            'total_biaya_rawat' => $totalBiaya,
            'dasar_hitung' => $dasarHitung,
            'hasil_mapping' => $hasil,
            'data_rawat' => $items->values()->all(),
        ];
    }

    private function mappingSnapshot(
        object $mapping,
        Collection $items,
        string $jenisPelayanan,
        array $sourceRules,
        string $nameSuffix,
        float $totalBiaya,
        int $jumlahData,
        string $jenisMapping,
        float $nilaiMapping,
        float $dasarHitung,
        float $hasil
    ): array {
        $isPercent = $jenisMapping === 'persen';

        return [
            'mapping_premi_id' => (int) $mapping->mapping_premi_id,
            'jnsPremi_id' => (int) $mapping->jnsPremi_id,
            'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
            'kode_premi' => $mapping->kode_premi,
            'nama_premi' => $mapping->nama_premi,
            'kode_jenis_tindakan' => $mapping->kode_jenis_tindakan,
            'nama_jenis_tindakan' => $mapping->nama_jenis_tindakan.$nameSuffix,
            'jenis_pelayanan' => $jenisPelayanan,
            'jenis_mapping' => $jenisMapping,
            'jenis_mapping_label' => $isPercent
                ? 'Persentase dari total biaya rawat'
                : 'Nominal per data tindakan',
            'nilai_mapping' => round($nilaiMapping, 4),
            'formula_text' => $isPercent
                ? number_format($nilaiMapping, 2, ',', '.').'% x Rp '.number_format($totalBiaya, 0, ',', '.')
                : number_format($jumlahData, 0, ',', '.').' data x Rp '.number_format($nilaiMapping, 0, ',', '.'),
            'jumlah_data' => $jumlahData,
            'jumlah_pasien' => $items->pluck('no_rawat')->unique()->count(),
            'jumlah_source_table' => $items->pluck('source_table')->filter()->unique()->count(),
            'jumlah_dokter' => $items->pluck('kd_dokter')->filter()->unique()->count(),
            'jumlah_paramedis' => $items->pluck('nip')->filter()->unique()->count(),
            'jumlah_dialihkan_perawat' => $items
                ->where('route_reason', 'doctor_filter_non_selected')
                ->count(),
            'total_biaya_rawat' => round($totalBiaya, 2),
            'dasar_hitung' => round($dasarHitung, 2),
            'hasil_mapping' => round($hasil, 2),
            'source_rules' => $sourceRules,
            'source_breakdown' => $this->mappingBreakdown(
                $items,
                fn ($row) => data_get($row, 'source_label') ?: data_get($row, 'source_table') ?: '-',
                fn ($row, $key) => (string) $key,
                $jenisMapping,
                $nilaiMapping
            ),
            'tindakan_breakdown' => $this->mappingBreakdown(
                $items,
                fn ($row) => data_get($row, 'sumber_tindakan').'|'.data_get($row, 'kd_tindakan'),
                fn ($row, $key) => trim(data_get($row, 'kd_tindakan').' - '.data_get($row, 'nm_tindakan')),
                $jenisMapping,
                $nilaiMapping
            ),
            'penjamin_breakdown' => $this->mappingBreakdown(
                $items,
                fn ($row) => data_get($row, 'kd_pj') ?: '-',
                fn ($row, $key) => trim($key.' - '.(data_get($row, 'nama_penjamin') ?: '-')),
                $jenisMapping,
                $nilaiMapping
            ),
            'doctor_breakdown' => $this->mappingBreakdown(
                $items->filter(fn ($row) => filled(data_get($row, 'kd_dokter'))),
                fn ($row) => data_get($row, 'kd_dokter'),
                fn ($row, $key) => trim($key.' - '.(data_get($row, 'nm_dokter') ?: '-')),
                $jenisMapping,
                $nilaiMapping
            ),
            'paramedic_breakdown' => $this->mappingBreakdown(
                $items->filter(fn ($row) => filled(data_get($row, 'nip'))),
                fn ($row) => data_get($row, 'nip'),
                fn ($row, $key) => trim($key.' - '.(data_get($row, 'nama_petugas') ?: '-')),
                $jenisMapping,
                $nilaiMapping
            ),
            'route_breakdown' => $this->mappingBreakdown(
                $items,
                fn ($row) => data_get($row, 'route_label') ?: 'Normal',
                fn ($row, $key) => (string) $key,
                $jenisMapping,
                $nilaiMapping
            ),
            'sample_rows' => $items
                ->take(20)
                ->map(fn ($row) => [
                    'no_rawat' => data_get($row, 'no_rawat'),
                    'no_rkm_medis' => data_get($row, 'no_rkm_medis'),
                    'nm_pasien' => data_get($row, 'nm_pasien'),
                    'tanggal' => data_get($row, 'tanggal'),
                    'jam' => data_get($row, 'jam'),
                    'source_label' => data_get($row, 'source_label'),
                    'kd_tindakan' => data_get($row, 'kd_tindakan'),
                    'nm_tindakan' => data_get($row, 'nm_tindakan'),
                    'nm_dokter' => data_get($row, 'nm_dokter'),
                    'nama_petugas' => data_get($row, 'nama_petugas'),
                    'nama_penjamin' => data_get($row, 'nama_penjamin'),
                    'route_label' => data_get($row, 'route_label'),
                    'biaya_rawat' => round((float) data_get($row, 'biaya_rawat', 0), 2),
                ])
                ->values()
                ->all(),
        ];
    }

    private function mappingBreakdown(
        Collection $items,
        callable $keyResolver,
        callable $labelResolver,
        string $jenisMapping,
        float $nilaiMapping,
        int $limit = 8
    ): array {
        if ($items->isEmpty()) {
            return [];
        }

        $isPercent = $jenisMapping === 'persen';

        return $items
            ->groupBy(fn ($row) => $keyResolver($row))
            ->map(function (Collection $rows, string|int $key) use ($labelResolver, $isPercent, $nilaiMapping) {
                $totalBiaya = round((float) $rows->sum('biaya_rawat'), 2);
                $jumlahData = $rows->count();

                return [
                    'key' => (string) $key,
                    'label' => $labelResolver($rows->first(), $key) ?: (string) $key,
                    'jumlah_data' => $jumlahData,
                    'total_biaya_rawat' => $totalBiaya,
                    'hasil_mapping' => $isPercent
                        ? round($totalBiaya * ($nilaiMapping / 100), 2)
                        : round($jumlahData * $nilaiMapping, 2),
                ];
            })
            ->sortByDesc('hasil_mapping')
            ->take($limit)
            ->values()
            ->all();
    }

    private function rawatPayload(
        object $row,
        object $mapping,
        ?object $icuRange = null,
        ?object $nicuRange = null,
        string $sourceType = '',
        array $routeInfo = []
    ): array {
        return [
            'mapping_tindakan_id' => (int) $mapping->mapping_tindakan_id,
            'mapping_premi_id' => (int) $mapping->mapping_premi_id,
            'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
            'source_table' => $row->source_table,
            'source_label' => $row->source_label,
            'sumber_tindakan' => $row->sumber_tindakan,
            'route_as' => $routeInfo['route_as'] ?? null,
            'route_reason' => $routeInfo['route_reason'] ?? null,
            'route_label' => $routeInfo['route_label'] ?? null,
            'original_source_table' => $routeInfo['original_source_table'] ?? $row->source_table,
            'effective_source_table' => $routeInfo['effective_source_table'] ?? $row->source_table,
            'no_rawat' => $row->no_rawat,
            'no_rkm_medis' => $row->no_rkm_medis,
            'nm_pasien' => $row->nm_pasien,
            'kd_pj' => $row->kd_pj,
            'nama_penjamin' => $row->nama_penjamin,
            'tanggal' => $row->tanggal,
            'jam' => $this->cleanTime($row->jam),
            'kd_tindakan' => $row->kd_tindakan,
            'nm_tindakan' => $mapping->nm_tindakan ?: $row->nm_tindakan,
            'kd_dokter' => $row->kd_dokter,
            'nm_dokter' => $row->nm_dokter,
            'nip' => $row->nip,
            'nama_petugas' => $row->nama_petugas,
            'jenis_pelayanan_sumber' => $sourceType,
            'biaya_rawat' => round((float) $row->biaya_rawat, 2),
            'is_icu' => (bool) $icuRange,
            'is_nicu' => (bool) $nicuRange,
            'kd_kamar_icu' => $icuRange?->kd_kamar,
            'kd_kamar_nicu' => $nicuRange?->kd_kamar,
        ];
    }

    private function plotingGeneratorSource(
        string $key,
        string $label,
        string $table,
        string $typeColumn,
        string $countColumn,
        string $totalColumn,
        string $periode,
        string $jenisPelayanan,
        ?int $plotingId,
        bool $forUpdate = false
    ): array {
        if (! Schema::hasTable($table)) {
            return $this->emptyGeneratorSource(
                $key,
                $label,
                $table,
                $periode,
                $jenisPelayanan,
                'Tabel belum tersedia'
            );
        }

        if (! $plotingId) {
            return $this->emptyGeneratorSource(
                $key,
                $label,
                $table,
                $periode,
                $jenisPelayanan,
                'Plotting belum dikonfigurasi',
                'Pilih plotting premi pada konfigurasi Premi Bersama.'
            );
        }

        $query = DB::table($table)
            ->where('periode', $periode)
            ->where($typeColumn, $jenisPelayanan)
            ->where('plotingPremi_id', $plotingId);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $rows = $query->get();

        return $this->aggregateGeneratorRows(
            $key,
            $label,
            $table,
            $periode,
            $jenisPelayanan,
            $rows,
            $countColumn,
            $totalColumn,
            $plotingId
        );
    }

    private function plainGeneratorSource(
        string $key,
        string $label,
        string $table,
        string $typeColumn,
        string $countColumn,
        string $totalColumn,
        string $periode,
        string $jenisPelayanan,
        bool $forUpdate = false
    ): array {
        if (! Schema::hasTable($table)) {
            return $this->emptyGeneratorSource(
                $key,
                $label,
                $table,
                $periode,
                $jenisPelayanan,
                'Tabel belum tersedia'
            );
        }

        $query = DB::table($table)
            ->where('periode', $periode)
            ->where($typeColumn, $jenisPelayanan);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->aggregateGeneratorRows(
            $key,
            $label,
            $table,
            $periode,
            $jenisPelayanan,
            $query->get(),
            $countColumn,
            $totalColumn
        );
    }

    private function apotekGeneratorSource(
        string $periode,
        string $jenisPelayanan,
        bool $forUpdate = false
    ): array {
        $table = 'generate_apotek';

        if (! Schema::hasTable($table)) {
            return $this->emptyGeneratorSource(
                'apotek',
                'Apotek',
                $table,
                $periode,
                $jenisPelayanan,
                'Tabel belum tersedia'
            );
        }

        $query = DB::table($table)
            ->where('periode', $periode)
            ->where('jenis_apotek', $jenisPelayanan);

        if (Schema::hasColumn($table, 'kategori_premi')) {
            $query->where('kategori_premi', 'apotek');
        }

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->aggregateGeneratorRows(
            'apotek',
            'Apotek',
            $table,
            $periode,
            $jenisPelayanan,
            $query->get(),
            'jumlah_obat',
            'total_premi_bersama'
        );
    }

    private function aggregateGeneratorRows(
        string $key,
        string $label,
        string $table,
        string $periode,
        string $jenisPelayanan,
        Collection $rows,
        string $countColumn,
        string $totalColumn,
        ?int $plotingId = null
    ): array {
        if ($rows->isEmpty()) {
            return $this->emptyGeneratorSource(
                $key,
                $label,
                $table,
                $periode,
                $jenisPelayanan,
                'Belum digenerate'
            );
        }

        $lockedRows = $rows->where('is_locked', true);
        $first = $rows->first();
        $totalAsal = round((float) $rows->sum($totalColumn), 2);
        $totalDiambil = round((float) $lockedRows->sum($totalColumn), 2);
        $isLocked = $rows->count() > 0 && $lockedRows->count() === $rows->count();

        return [
            'source_key' => $key,
            'source_label' => $label,
            'source_table' => $table,
            'source_id' => null,
            'source_periode' => $periode,
            'source_type' => $jenisPelayanan,
            'plotingPremi_id' => $plotingId ?: ($first->plotingPremi_id ?? null),
            'kode_ploting' => $first->kode_ploting ?? null,
            'nama_ploting' => $first->nama_ploting ?? null,
            'jumlah_data' => (int) $rows->sum($countColumn),
            'total_asal' => $totalAsal,
            'total_diambil' => $totalDiambil,
            'is_locked' => $isLocked,
            'status_label' => $isLocked ? 'Terkunci' : 'Belum terkunci',
            'note' => $isLocked
                ? "{$rows->count()} data {$label} terkunci."
                : "{$lockedRows->count()} dari {$rows->count()} data {$label} terkunci; hanya nilai terkunci yang diambil.",
            'raw_snapshot' => [
                'generated_count' => $rows->count(),
                'locked_count' => $lockedRows->count(),
                'count_column' => $countColumn,
                'total_column' => $totalColumn,
                'items' => $rows
                    ->map(fn ($row) => [
                        'id' => (int) ($row->id ?? 0),
                        'periode' => $row->periode ?? $periode,
                        'jenis' => $row->jenis_ugd
                            ?? $row->jenis_vk
                            ?? $row->jenis_kamar
                            ?? $row->jenis_bhp
                            ?? $row->jenis_laboratorium
                            ?? $row->jenis_radiologi
                            ?? $row->jenis_apotek
                            ?? $row->jenis_gizi
                            ?? $row->jenis_nicu
                            ?? $row->jenis_operasi
                            ?? $jenisPelayanan,
                        'jumlah_data' => (int) data_get($row, $countColumn, 0),
                        'total' => round((float) data_get($row, $totalColumn, 0), 2),
                        'is_locked' => (bool) ($row->is_locked ?? false),
                        'ploting_label' => trim((($row->kode_ploting ?? null) ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '')),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function emptyGeneratorSource(
        string $key,
        string $label,
        string $table,
        string $periode,
        string $jenisPelayanan,
        string $status,
        ?string $note = null
    ): array {
        return [
            'source_key' => $key,
            'source_label' => $label,
            'source_table' => $table,
            'source_id' => null,
            'source_periode' => $periode,
            'source_type' => $jenisPelayanan,
            'plotingPremi_id' => null,
            'kode_ploting' => null,
            'nama_ploting' => null,
            'jumlah_data' => 0,
            'total_asal' => 0,
            'total_diambil' => 0,
            'is_locked' => false,
            'status_label' => $status,
            'note' => $note ?: "{$label} {$jenisPelayanan} periode {$periode} belum siap.",
            'raw_snapshot' => [
                'generated_count' => 0,
                'locked_count' => 0,
                'items' => [],
            ],
        ];
    }

    private function generatorSummary(Collection $sources): array
    {
        return [
            'jumlah_sumber_generator' => $sources->count(),
            'jumlah_sumber_terkunci' => $sources->where('is_locked', true)->count(),
            'total_generator_sumber' => round((float) $sources->sum('total_diambil'), 2),
        ];
    }

    private function icuRanges(Collection $noRawats): Collection
    {
        $rows = collect();

        foreach ($noRawats->chunk(700) as $chunk) {
            $rows = $rows->merge(
                DB::connection('mysql_khanza')
                    ->table('kamar_inap')
                    ->join('kamar', 'kamar.kd_kamar', '=', 'kamar_inap.kd_kamar')
                    ->select([
                        'kamar_inap.no_rawat',
                        'kamar_inap.kd_kamar',
                        'kamar_inap.tgl_masuk',
                        'kamar_inap.jam_masuk',
                        'kamar_inap.tgl_keluar',
                        'kamar_inap.jam_keluar',
                        'kamar.kd_bangsal',
                    ])
                    ->whereIn('no_rawat', $chunk->values()->all())
                    ->where('kamar.kd_bangsal', '=', 'ICU')
                    ->orderBy('kamar_inap.tgl_masuk')
                    ->orderBy('kamar_inap.jam_masuk')
                    ->get()
            );
        }

        return $rows->groupBy('no_rawat');
    }

    private function nicuRanges(Collection $noRawats): Collection
    {
        $rows = collect();

        foreach ($noRawats->chunk(700) as $chunk) {
            $rows = $rows->merge(
                DB::connection('mysql_khanza')
                    ->table('kamar_inap')
                    ->join('kamar', 'kamar.kd_kamar', '=', 'kamar_inap.kd_kamar')
                    ->select([
                        'kamar_inap.no_rawat',
                        'kamar_inap.kd_kamar',
                        'kamar_inap.tgl_masuk',
                        'kamar_inap.jam_masuk',
                        'kamar_inap.tgl_keluar',
                        'kamar_inap.jam_keluar',
                        'kamar.kelas',
                    ])
                    ->whereIn('no_rawat', $chunk->values()->all())
                    ->where('kamar.kelas', '=', 'NICU')
                    ->orderBy('kamar_inap.tgl_masuk')
                    ->orderBy('kamar_inap.jam_masuk')
                    ->get()
            );
        }

        return $rows->groupBy('no_rawat');
    }

    private function matchingRoomRange(object $row, Collection $ranges): ?object
    {
        if ($ranges->isEmpty()) {
            return null;
        }

        $actionAt = $this->actionDateTime($row->tanggal, $row->jam);

        return $ranges->first(function ($range) use ($actionAt) {
            return $actionAt->betweenIncluded(
                $this->rangeStart($range),
                $this->rangeEnd($range)
            );
        });
    }

    private function allowedTablesByAction(Collection $sourceMappings): Collection
    {
        return $sourceMappings
            ->groupBy('jnsTindakan_id')
            ->map(function (Collection $items) {
                return $items
                    ->flatMap(fn ($item) => $this->expandSourcePattern($item->source_pattern))
                    ->unique()
                    ->values();
            });
    }

    private function sourceAllowedForAction(
        int $jnsTindakanId,
        string $sourceTable,
        Collection $allowedTablesByAction
    ): bool {
        $allowedTables = $allowedTablesByAction->get($jnsTindakanId);

        return ! $allowedTables || $allowedTables->contains($sourceTable);
    }

    private function doctorAllowedForMappedRow(
        object $row,
        int $jnsTindakanId,
        string $sourceType,
        Collection $doctorCodes,
        Collection $doctorActionIds
    ): bool {
        if (
            $doctorCodes->isEmpty()
            || $doctorActionIds->isEmpty()
            || $sourceType === 'bpjs_karcis'
        ) {
            return true;
        }

        if (! in_array($row->source_table, self::DOCTOR_SOURCE_TABLES, true)) {
            return true;
        }

        if (! $doctorActionIds->contains($jnsTindakanId)) {
            return true;
        }

        return $doctorCodes->contains((string) $row->kd_dokter);
    }

    private function rowHasFilteredDoctorAction(
        object $row,
        Collection $actionMappings,
        string $sourceType,
        Collection $doctorCodes,
        Collection $doctorActionIds,
        Collection $allowedTablesByAction
    ): bool {
        if (
            $doctorCodes->isEmpty()
            || $doctorActionIds->isEmpty()
            || $sourceType === 'bpjs_karcis'
            || ! in_array($row->source_table, self::DOCTOR_SOURCE_TABLES, true)
        ) {
            return false;
        }

        return $actionMappings->contains(function ($mapping) use ($row, $doctorActionIds, $allowedTablesByAction) {
            $jnsTindakanId = (int) $mapping->jnsTindakan_id;

            return $doctorActionIds->contains($jnsTindakanId)
                && $this->sourceAllowedForAction(
                    $jnsTindakanId,
                    $row->source_table,
                    $allowedTablesByAction
                );
        });
    }

    private function doctorRouteInfoForMappedRow(
        object $row,
        int $jnsTindakanId,
        string $sourceType,
        Collection $doctorCodes,
        Collection $doctorActionIds,
        bool $hasFilteredDoctorAction,
        bool $doctorMatchesFilter
    ): array {
        if (
            $doctorCodes->isEmpty()
            || $doctorActionIds->isEmpty()
            || $sourceType === 'bpjs_karcis'
            || ! $hasFilteredDoctorAction
            || $doctorMatchesFilter
            || $doctorActionIds->contains($jnsTindakanId)
            || ! in_array($row->source_table, self::DOCTOR_SOURCE_TABLES, true)
        ) {
            return [];
        }

        $effectiveSourceTable = self::DOCTOR_TO_PARAMEDIS_SOURCE_TABLE[$row->source_table] ?? null;

        if (! $effectiveSourceTable) {
            return [];
        }

        return [
            'route_as' => 'paramedis',
            'route_reason' => 'doctor_filter_non_selected',
            'route_label' => 'Dialihkan ke tindakan perawat',
            'original_source_table' => $row->source_table,
            'effective_source_table' => $effectiveSourceTable,
        ];
    }

    private function sourceRulesForAction(int $jnsTindakanId, Collection $sourceMappings): array
    {
        return $sourceMappings
            ->where('jnsTindakan_id', $jnsTindakanId)
            ->map(fn ($item) => [
                'source_pattern' => $item->source_pattern,
                'source_label' => data_get(self::SOURCE_PATTERNS, $item->source_pattern.'.label', $item->source_pattern),
                'tables' => $this->expandSourcePattern($item->source_pattern),
            ])
            ->values()
            ->all();
    }

    private function sourceMappingsPayload(Collection $sourceMappings): array
    {
        return $sourceMappings
            ->map(fn ($item) => [
                'source_pattern' => $item->source_pattern,
                'source_label' => data_get(self::SOURCE_PATTERNS, $item->source_pattern.'.label', $item->source_pattern),
                'jnsTindakan_id' => (int) $item->jnsTindakan_id,
                'kode_jenis_tindakan' => $item->kode_jenis_tindakan,
                'nama_jenis_tindakan' => $item->nama_jenis_tindakan,
            ])
            ->values()
            ->all();
    }

    private function expandSourcePattern(string $pattern): array
    {
        if (isset(self::SOURCE_PATTERNS[$pattern])) {
            return self::SOURCE_PATTERNS[$pattern]['tables'];
        }

        return collect(self::SOURCE_TABLES)->pluck('table')->contains($pattern)
            ? [$pattern]
            : [];
    }

    private function periodRange(string $periode): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfDay();

        return [
            'start' => $start,
            'end' => $start->copy()->addMonthNoOverflow()->startOfDay(),
        ];
    }

    private function actionDateTime(string $date, ?string $time): Carbon
    {
        return Carbon::parse($date.' '.($this->cleanTime($time) ?: '00:00:00'));
    }

    private function rangeStart(object $range): Carbon
    {
        return Carbon::parse($this->cleanDate($range->tgl_masuk).' '.($this->cleanTime($range->jam_masuk) ?: '00:00:00'));
    }

    private function rangeEnd(object $range): Carbon
    {
        $date = $this->cleanDate($range->tgl_keluar);

        if (! $date) {
            return Carbon::parse('9999-12-31 23:59:59');
        }

        return Carbon::parse($date.' '.($this->cleanTime($range->jam_keluar) ?: '23:59:59'));
    }

    private function cleanDate(?string $date): ?string
    {
        $date = trim((string) $date);

        return $date === '' || $date === '0000-00-00' ? null : $date;
    }

    private function cleanTime(?string $time): ?string
    {
        $time = trim((string) $time);

        return $time === '' ? null : $time;
    }
}
