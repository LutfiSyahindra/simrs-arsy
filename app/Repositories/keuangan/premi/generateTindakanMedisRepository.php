<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generateTindakanMedisDetailModel;
use App\Models\dbSimrs\generateTindakanMedisDistributionModel;
use App\Models\dbSimrs\generateTindakanMedisModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class generateTindakanMedisRepository
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
        return generateTindakanMedisModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name', 'jnsPremi:id,pembagi'])
            ->withCount(['details', 'distributions'])
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
        $config = DB::table('generate_tindakan_medis_configs')->first();

        if ($config) {
            return $config;
        }

        $defaultPremiId = DB::table('master_jenis_premi')
            ->where(function ($query) {
                $query
                    ->where('jenis', 'like', '%Premi Medis%')
                    ->orWhere('jenis', 'like', '%Tindakan Medis%');
            })
            ->where('jenis', 'not like', '%Non%')
            ->orderBy('jenis')
            ->value('id');
        $now = now();
        $payload = [
            'jnsPremi_id' => $defaultPremiId,
            'bpjs_source_mode' => PremiSourcePeriod::MODE_PREVIOUS,
            'distribution_mode' => 'split_evenly',
            'ignore_icu' => true,
            'ignore_nicu' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_ugd')) {
            $payload['bpjs_ignore_ugd'] = false;
        }

        if (Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_vk')) {
            $payload['bpjs_ignore_vk'] = false;
        }

        $id = DB::table('generate_tindakan_medis_configs')->insertGetId($payload);

        return DB::table('generate_tindakan_medis_configs')->where('id', $id)->first();
    }

    public function saveConfig(
        int $jnsPremiId,
        string $bpjsSourceMode,
        string $distributionMode,
        bool $ignoreIcu,
        bool $ignoreNicu,
        bool $bpjsIgnoreUgd,
        bool $bpjsIgnoreVk,
        array $sourceMappings,
        array $karcisTindakanIds = [],
        array $doctorCodes = [],
        array $doctorTindakanIds = []
    ): object {
        return DB::transaction(function () use (
            $jnsPremiId,
            $bpjsSourceMode,
            $distributionMode,
            $ignoreIcu,
            $ignoreNicu,
            $bpjsIgnoreUgd,
            $bpjsIgnoreVk,
            $sourceMappings,
            $karcisTindakanIds,
            $doctorCodes,
            $doctorTindakanIds
        ) {
            $config = $this->getConfig();
            $updates = [
                'jnsPremi_id' => $jnsPremiId,
                'bpjs_source_mode' => $bpjsSourceMode,
                'distribution_mode' => $distributionMode,
                'ignore_icu' => $ignoreIcu,
                'ignore_nicu' => $ignoreNicu,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_ugd')) {
                $updates['bpjs_ignore_ugd'] = $bpjsIgnoreUgd;
            }

            if (Schema::hasColumn('generate_tindakan_medis_configs', 'bpjs_ignore_vk')) {
                $updates['bpjs_ignore_vk'] = $bpjsIgnoreVk;
            }

            DB::table('generate_tindakan_medis_configs')
                ->where('id', $config->id)
                ->update($updates);

            DB::table('generate_tindakan_medis_config_source')
                ->where('config_id', $config->id)
                ->delete();

            $rows = collect($sourceMappings)
                ->map(fn (array $item) => [
                    'config_id' => $config->id,
                    'source_pattern' => (string) $item['source_pattern'],
                    'jnsTindakan_id' => (int) $item['jnsTindakan_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->unique(fn (array $item) => $item['source_pattern'].'|'.$item['jnsTindakan_id'])
                ->values();

            if ($rows->isNotEmpty()) {
                DB::table('generate_tindakan_medis_config_source')->insert($rows->all());
            }

            $this->replaceKarcisConfig($karcisTindakanIds);
            $this->replaceDoctorConfig((int) $config->id, $doctorCodes);
            $this->replaceDoctorActionConfig((int) $config->id, $doctorTindakanIds);

            return DB::table('generate_tindakan_medis_configs')
                ->where('id', $config->id)
                ->first();
        });
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

    public function getSelectedDoctors(?int $configId = null): Collection
    {
        if (! Schema::hasTable('generate_tindakan_medis_config_doctor')) {
            return collect();
        }

        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_tindakan_medis_config_doctor')
            ->select('kd_dokter', 'nm_dokter')
            ->where('config_id', $configId)
            ->orderBy('nm_dokter')
            ->get();
    }

    public function getSelectedDoctorActionIds(?int $configId = null): Collection
    {
        if (! Schema::hasTable('generate_tindakan_medis_config_doctor_action')) {
            return collect();
        }

        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_tindakan_medis_config_doctor_action')
            ->where('config_id', $configId)
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function getSelectedDoctorActions(?int $configId = null): Collection
    {
        if (! Schema::hasTable('generate_tindakan_medis_config_doctor_action')) {
            return collect();
        }

        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_tindakan_medis_config_doctor_action as da')
            ->join('master_jenis_tindakan as jt', 'jt.id', '=', 'da.jnsTindakan_id')
            ->select([
                'jt.id',
                'jt.kode',
                'jt.jenis',
            ])
            ->where('da.config_id', $configId)
            ->orderBy('jt.jenis')
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

    public function getKarcisConfigOptions(): Collection
    {
        return DB::table('master_jenis_tindakan as jt')
            ->leftJoin('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'jt.id')
            ->leftJoin('mapping_premi as mp', 'mp.jnsTindakan_id', '=', 'jt.id')
            ->select([
                'jt.id',
                'jt.kode',
                'jt.jenis',
                DB::raw('COUNT(DISTINCT mt.id) as jumlah_mapping_tindakan'),
                DB::raw('COUNT(DISTINCT mp.id) as jumlah_mapping_premi'),
            ])
            ->groupBy('jt.id', 'jt.kode', 'jt.jenis')
            ->orderBy('jt.jenis')
            ->get();
    }

    public function getKarcisTindakanIds(): Collection
    {
        return DB::table('generate_tindakan_medis_karcis_config')
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function saveKarcisConfig(array $tindakanIds): void
    {
        DB::transaction(function () use ($tindakanIds) {
            $this->replaceKarcisConfig($tindakanIds);
        });
    }

    private function replaceKarcisConfig(array $tindakanIds): void
    {
        DB::table('generate_tindakan_medis_karcis_config')->delete();

        if (empty($tindakanIds)) {
            return;
        }

        $now = now();
        DB::table('generate_tindakan_medis_karcis_config')->insert(
            collect($tindakanIds)
                ->unique()
                ->map(fn ($id) => [
                    'jnsTindakan_id' => (int) $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all()
        );
    }

    private function replaceDoctorActionConfig(int $configId, array $tindakanIds): void
    {
        if (! Schema::hasTable('generate_tindakan_medis_config_doctor_action')) {
            return;
        }

        DB::table('generate_tindakan_medis_config_doctor_action')
            ->where('config_id', $configId)
            ->delete();

        if (empty($tindakanIds)) {
            return;
        }

        $now = now();
        DB::table('generate_tindakan_medis_config_doctor_action')->insert(
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

    private function replaceDoctorConfig(int $configId, array $doctorCodes): void
    {
        if (! Schema::hasTable('generate_tindakan_medis_config_doctor')) {
            return;
        }

        DB::table('generate_tindakan_medis_config_doctor')
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
        DB::table('generate_tindakan_medis_config_doctor')->insert(
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

    public function getConfigSourceMappings(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_tindakan_medis_config_source as cs')
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

    public function getMappedPegawai(int $jnsPremiId): Collection
    {
        return DB::table('mapping_premi_pegawai as mpp')
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
    }

    public function getRecipientBonuses(
        string $periode,
        string $jenisPelayanan,
        Collection $niks
    ): Collection {
        $niks = $niks
            ->filter()
            ->map(fn ($nik) => (string) $nik)
            ->unique()
            ->values();

        return collect([
            'icu' => $this->medicalBonusByType(
                'generate_icu',
                'jenis_icu',
                $periode,
                $jenisPelayanan,
                $niks,
                'ICU'
            ),
            'nicu' => $this->medicalBonusByType(
                'generate_nicu',
                'jenis_nicu',
                $periode,
                $jenisPelayanan,
                $niks,
                'NICU'
            ),
        ]);
    }

    public function getDependencyOptions(string $sourcePeriode, string $jenisPelayanan): array
    {
        return [
            'ugd' => $this->dependencyOptions(
                'generate_ugd',
                'jenis_ugd',
                'jumlah_pasien',
                'total_ugd',
                $sourcePeriode,
                $jenisPelayanan
            ),
            'vk' => $this->dependencyOptions(
                'generate_vk',
                'jenis_vk',
                'jumlah_tindakan',
                'total_vk',
                $sourcePeriode,
                $jenisPelayanan
            ),
        ];
    }

    public function getDependencies(
        string $sourcePeriode,
        string $jenisPelayanan,
        ?int $ugdPlotingId,
        ?int $vkPlotingId,
        bool $forUpdate = false
    ): array {
        return [
            'ugd' => $this->dependencyGroup(
                'generate_ugd',
                'jenis_ugd',
                'jumlah_pasien',
                'total_ugd',
                $sourcePeriode,
                $jenisPelayanan,
                $ugdPlotingId,
                $forUpdate
            ),
            'vk' => $this->dependencyGroup(
                'generate_vk',
                'jenis_vk',
                'jumlah_tindakan',
                'total_vk',
                $sourcePeriode,
                $jenisPelayanan,
                $vkPlotingId,
                $forUpdate
            ),
        ];
    }

    public function calculate(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId,
        object $config,
        Collection $sourceMappings
    ): array {
        $karcisTindakanIds = $this->getKarcisTindakanIds();
        $mappings = $this->getCalculationMappings(
            $jenisPelayanan,
            $jnsPremiId,
            $karcisTindakanIds
        );
        $doctorCodes = $this->getSelectedDoctors((int) $config->id)
            ->pluck('kd_dokter')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();
        $doctorActionIds = $this->getSelectedDoctorActionIds((int) $config->id);
        $selectedPremi = $this->findPremi($jnsPremiId);
        $range = PremiSourcePeriod::range(
            $periode,
            $jenisPelayanan,
            $config->bpjs_source_mode ?? null
        );

        if ($mappings->isEmpty()) {
            return [
                'transactions' => collect(),
                'ignored' => collect(),
                'details' => collect(),
                'source_periode' => PremiSourcePeriod::resolve(
                    $periode,
                    $jenisPelayanan,
                    $config->bpjs_source_mode ?? null
                ),
                'source_tgl_awal' => $range['start']->toDateString(),
                'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
                'jnsPremi_id' => $jnsPremiId,
                'kode_premi' => $selectedPremi?->kode,
                'nama_premi' => $selectedPremi?->jenis,
                'pembagi' => max(1, (int) ($selectedPremi?->pembagi ?? 1)),
                'jumlah_transaksi' => 0,
                'jumlah_pasien' => 0,
                'jumlah_jenis_tindakan' => 0,
                'jumlah_mapping_premi' => 0,
                'jumlah_terabaikan_icu' => 0,
                'jumlah_terabaikan_nicu' => 0,
                'total_biaya_rawat' => 0,
                'total_mapping_premi' => 0,
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

        if ($jenisPelayanan === 'umum' && $karcisTindakanIds->isNotEmpty()) {
            $karcisMappings = $mappings
                ->filter(fn ($mapping) => $karcisTindakanIds->contains(
                    (int) $mapping->jnsTindakan_id
                ))
                ->values();

            if ($karcisMappings->isNotEmpty()) {
                $karcisRange = PremiSourcePeriod::range(
                    $periode,
                    'bpjs',
                    $config->bpjs_source_mode ?? null
                );
                $karcisPayload = $this->collectTransactions(
                    'bpjs',
                    $karcisRange['start'],
                    $karcisRange['end'],
                    $karcisMappings,
                    $sourceMappings,
                    (bool) ($config->ignore_icu ?? true),
                    (bool) ($config->ignore_nicu ?? true),
                    'bpjs_karcis',
                    collect(),
                    collect()
                );
                $transactionsPayload['transactions'] = $transactionsPayload['transactions']
                    ->merge($karcisPayload['transactions']);
                $transactionsPayload['ignored'] = $transactionsPayload['ignored']
                    ->merge($karcisPayload['ignored']);
            }
        }

        $transactions = $transactionsPayload['transactions'];
        $transactionsByAction = $transactions->groupBy('jnsTindakan_id');
        $details = $mappings
            ->unique('mapping_premi_id')
            ->flatMap(function ($mapping) use ($transactionsByAction, $jenisPelayanan, $sourceMappings) {
                $items = $transactionsByAction
                    ->get((int) $mapping->jnsTindakan_id, collect())
                    ->values();

                if ($jenisPelayanan !== 'umum') {
                    return collect([
                        $this->makeDetail(
                            $mapping,
                            $items,
                            $jenisPelayanan,
                            $sourceMappings
                        ),
                    ]);
                }

                [$karcisBpjsItems, $umumItems] = $items
                    ->partition(fn ($item) => data_get($item, 'jenis_pelayanan_sumber') === 'bpjs_karcis');
                $details = collect();

                if ($umumItems->isNotEmpty()) {
                    $details->push(
                        $this->makeDetail(
                            $mapping,
                            $umumItems->values(),
                            $jenisPelayanan,
                            $sourceMappings,
                            (float) $mapping->nilai_umum,
                            '',
                            $mapping->jenis_umum
                        )
                    );
                }

                if ($karcisBpjsItems->isNotEmpty()) {
                    $details->push(
                        $this->makeDetail(
                            $mapping,
                            $karcisBpjsItems->values(),
                            'bpjs',
                            $sourceMappings,
                            (float) $mapping->nilai_bpjs,
                            ' (Karcis BPJS)',
                            $mapping->jenis_bpjs
                        )
                    );
                }

                return $details;
            })
            ->filter(fn (array $detail) => $detail['jumlah_data'] > 0)
            ->sortBy('nama_jenis_tindakan')
            ->values();

        return [
            'transactions' => $transactions,
            'ignored' => $transactionsPayload['ignored'],
            'details' => $details,
            'source_periode' => PremiSourcePeriod::resolve(
                $periode,
                $jenisPelayanan,
                $config->bpjs_source_mode ?? null
            ),
            'source_tgl_awal' => $range['start']->toDateString(),
            'source_tgl_akhir' => $range['end']->copy()->subDay()->toDateString(),
            'jnsPremi_id' => $jnsPremiId,
            'kode_premi' => $selectedPremi?->kode,
            'nama_premi' => $selectedPremi?->jenis,
            'pembagi' => max(1, (int) ($selectedPremi?->pembagi ?? 1)),
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_pasien' => $transactions->pluck('no_rawat')->unique()->count(),
            'jumlah_jenis_tindakan' => $transactions->pluck('jnsTindakan_id')->unique()->count(),
            'jumlah_mapping_premi' => $details->count(),
            'jumlah_terabaikan_icu' => $transactionsPayload['ignored']
                ->where('is_icu', true)
                ->count(),
            'jumlah_terabaikan_nicu' => $transactionsPayload['ignored']
                ->where('is_nicu', true)
                ->count(),
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat'), 2),
            'total_mapping_premi' => round((float) $details->sum('hasil_mapping'), 2),
        ];
    }

    public function findByPeriodAndType(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId
    ): ?generateTindakanMedisModel {
        return generateTindakanMedisModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name', 'jnsPremi:id,pembagi'])
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('nama_jenis_tindakan')
                    ->orderBy('nama_premi'),
                'distributions' => fn ($query) => $query->orderBy('pegawai_name'),
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
    ): ?generateTindakanMedisModel {
        return generateTindakanMedisModel::query()
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
        array $dependencies,
        array $calculation,
        Collection $sourceMappings
    ): generateTindakanMedisModel {
        $totalUgd = (float) data_get($dependencies, 'ugd.total', 0);
        $totalVk = (float) data_get($dependencies, 'vk.total', 0);
        $totalMapping = (float) $calculation['total_mapping_premi'];
        $grandTotal = round($totalMapping + $totalUgd + $totalVk, 2);
        $pembagi = max(1, (int) ($calculation['pembagi'] ?? 1));

        return generateTindakanMedisModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_pelayanan' => $jenisPelayanan,
                'jnsPremi_id' => $jnsPremiId,
            ],
            [
                'source_periode' => $calculation['source_periode'],
                'source_tgl_awal' => $calculation['source_tgl_awal'],
                'source_tgl_akhir' => $calculation['source_tgl_akhir'],
                'kode_premi' => $calculation['kode_premi'],
                'nama_premi' => $calculation['nama_premi'],
                'ugd_plotingPremi_id' => data_get($dependencies, 'ugd.plotingPremi_id'),
                'ugd_kode_ploting' => data_get($dependencies, 'ugd.kode_ploting'),
                'ugd_nama_ploting' => data_get($dependencies, 'ugd.nama_ploting'),
                'vk_plotingPremi_id' => data_get($dependencies, 'vk.plotingPremi_id'),
                'vk_kode_ploting' => data_get($dependencies, 'vk.kode_ploting'),
                'vk_nama_ploting' => data_get($dependencies, 'vk.nama_ploting'),
                'bpjs_source_mode' => PremiSourcePeriod::normalizeMode(
                    $config->bpjs_source_mode ?? null,
                    $jenisPelayanan
                ),
                'ignore_icu' => (bool) ($config->ignore_icu ?? true),
                'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
                'bpjs_ignore_ugd' => $jenisPelayanan === 'bpjs'
                    && (bool) ($config->bpjs_ignore_ugd ?? false),
                'bpjs_ignore_vk' => $jenisPelayanan === 'bpjs'
                    && (bool) ($config->bpjs_ignore_vk ?? false),
                'jumlah_transaksi' => $calculation['jumlah_transaksi'],
                'jumlah_pasien' => $calculation['jumlah_pasien'],
                'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan'],
                'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi'],
                'jumlah_terabaikan_icu' => $calculation['jumlah_terabaikan_icu'],
                'jumlah_terabaikan_nicu' => $calculation['jumlah_terabaikan_nicu'],
                'total_biaya_rawat' => $calculation['total_biaya_rawat'],
                'total_mapping_premi' => $totalMapping,
                'total_ugd' => $totalUgd,
                'total_vk' => $totalVk,
                'grand_total' => $grandTotal,
                'pembagi' => $pembagi,
                'total_final' => round($grandTotal / $pembagi, 2),
                'distribution_mode' => $config->distribution_mode ?? 'split_evenly',
                'jumlah_penerima' => 0,
                'total_dasar_dibagikan' => 0,
                'total_tambahan_icu' => 0,
                'total_tambahan_nicu' => 0,
                'total_dibagikan' => 0,
                'config_snapshot' => [
                    'bpjs_source_mode' => PremiSourcePeriod::normalizeMode(
                        $config->bpjs_source_mode ?? null,
                        $jenisPelayanan
                    ),
                    'distribution_mode' => $config->distribution_mode ?? 'split_evenly',
                    'ignore_icu' => (bool) ($config->ignore_icu ?? true),
                    'ignore_nicu' => (bool) ($config->ignore_nicu ?? true),
                    'bpjs_ignore_ugd' => $jenisPelayanan === 'bpjs'
                        && (bool) ($config->bpjs_ignore_ugd ?? false),
                    'bpjs_ignore_vk' => $jenisPelayanan === 'bpjs'
                        && (bool) ($config->bpjs_ignore_vk ?? false),
                    'source_mappings' => $this->sourceMappingsPayload($sourceMappings),
                    'karcis_tindakan_ids' => $this->getKarcisTindakanIds()->all(),
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

    public function replaceDetails(
        generateTindakanMedisModel $header,
        Collection $details
    ): void {
        $header->details()->delete();

        if ($details->isEmpty()) {
            return;
        }

        $now = now();
        $details
            ->map(fn (array $detail) => [
                ...$detail,
                'generate_tindakan_medis_id' => $header->id,
                'source_rules' => json_encode(
                    $detail['source_rules'],
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
            ->each(fn (Collection $chunk) => generateTindakanMedisDetailModel::query()
                ->insert($chunk->all()));
    }

    public function replaceDistributions(
        generateTindakanMedisModel $header,
        Collection $distributions
    ): void {
        $header->distributions()->delete();

        $summary = [
            'distribution_mode' => data_get($distributions->first(), 'distribution_mode', $header->distribution_mode ?? 'split_evenly'),
            'jumlah_penerima' => $distributions->count(),
            'total_dasar_dibagikan' => round((float) $distributions->sum('total_dasar'), 2),
            'total_tambahan_icu' => round((float) $distributions->sum('total_icu'), 2),
            'total_tambahan_nicu' => round((float) $distributions->sum('total_nicu'), 2),
            'total_dibagikan' => round((float) $distributions->sum('total_diterima'), 2),
            'updated_at' => now(),
        ];

        if ($distributions->isNotEmpty()) {
            $now = now();
            $distributions
                ->map(fn (array $distribution) => [
                    'generate_tindakan_medis_id' => $header->id,
                    'jnsPremi_id' => $distribution['jnsPremi_id'],
                    'nik' => $distribution['nik'],
                    'pegawai_name' => $distribution['pegawai_name'],
                    'pegawai_position' => $distribution['pegawai_position'],
                    'distribution_mode' => $distribution['distribution_mode'],
                    'total_final' => $distribution['total_final'],
                    'jumlah_penerima' => $distribution['jumlah_penerima'],
                    'total_dasar' => $distribution['total_dasar'],
                    'has_icu_bonus' => $distribution['has_icu_bonus'],
                    'total_icu' => $distribution['total_icu'],
                    'icu_bonus_info' => json_encode(
                        $distribution['icu_bonus_info'] ?? [],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'has_nicu_bonus' => $distribution['has_nicu_bonus'],
                    'total_nicu' => $distribution['total_nicu'],
                    'nicu_bonus_info' => json_encode(
                        $distribution['nicu_bonus_info'] ?? [],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'total_diterima' => $distribution['total_diterima'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->chunk(100)
                ->each(fn (Collection $chunk) => generateTindakanMedisDistributionModel::query()
                    ->insert($chunk->all()));
        }

        DB::table('generate_tindakan_medis')
            ->where('id', $header->id)
            ->update($summary);
    }

    public function findWithDetails(int $id): ?generateTindakanMedisModel
    {
        return generateTindakanMedisModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name', 'jnsPremi:id,pembagi'])
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('nama_jenis_tindakan')
                    ->orderBy('nama_premi'),
                'distributions' => fn ($query) => $query->orderBy('pegawai_name'),
            ])
            ->find($id);
    }

    public function findForUpdate(int $id): ?generateTindakanMedisModel
    {
        return generateTindakanMedisModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function updateLock(
        generateTindakanMedisModel $header,
        bool $isLocked,
        ?int $userId = null
    ): generateTindakanMedisModel {
        DB::table('generate_tindakan_medis')
            ->where('id', $header->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateTindakanMedisModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($header->id);
    }

    private function dependencyOptions(
        string $table,
        string $typeColumn,
        string $countColumn,
        string $totalColumn,
        string $sourcePeriode,
        string $jenisPelayanan
    ): Collection {
        return DB::table($table)
            ->select([
                'plotingPremi_id',
                'kode_ploting',
                'nama_ploting',
                DB::raw('COUNT(*) as generated_count'),
                DB::raw("SUM({$countColumn}) as jumlah_data"),
                DB::raw("SUM({$totalColumn}) as total"),
                DB::raw('SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked_count'),
                DB::raw('MAX(updated_at) as updated_at'),
            ])
            ->where('periode', $sourcePeriode)
            ->where($typeColumn, $jenisPelayanan)
            ->whereNotNull('plotingPremi_id')
            ->groupBy('plotingPremi_id', 'kode_ploting', 'nama_ploting')
            ->orderBy('nama_ploting')
            ->get()
            ->map(fn ($row) => $this->dependencyOptionPayload($row));
    }

    private function dependencyGroup(
        string $table,
        string $typeColumn,
        string $countColumn,
        string $totalColumn,
        string $sourcePeriode,
        string $jenisPelayanan,
        ?int $plotingId,
        bool $forUpdate
    ): ?array {
        if (! $plotingId) {
            return null;
        }

        $query = DB::table($table)
            ->where('periode', $sourcePeriode)
            ->where($typeColumn, $jenisPelayanan)
            ->where('plotingPremi_id', $plotingId);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $first = $rows->first();

        return [
            'plotingPremi_id' => (int) $first->plotingPremi_id,
            'kode_ploting' => $first->kode_ploting,
            'nama_ploting' => $first->nama_ploting,
            'ploting_label' => $this->plotingLabel($first),
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_data' => (int) $rows->sum($countColumn),
            'total' => round((float) $rows->sum($totalColumn), 2),
            'is_locked' => $rows->count() > 0 && $rows->every(fn ($row) => (bool) $row->is_locked),
            'updated_at' => optional($rows->max('updated_at') ? Carbon::parse($rows->max('updated_at')) : null)
                ->format('d-m-Y H:i'),
            'items' => $rows,
        ];
    }

    private function dependencyOptionPayload(object $row): array
    {
        return [
            'id' => (int) $row->plotingPremi_id,
            'plotingPremi_id' => (int) $row->plotingPremi_id,
            'kode_ploting' => $row->kode_ploting,
            'nama_ploting' => $row->nama_ploting,
            'ploting_label' => $this->plotingLabel($row),
            'generated_count' => (int) $row->generated_count,
            'locked_count' => (int) $row->locked_count,
            'jumlah_data' => (int) $row->jumlah_data,
            'total' => round((float) $row->total, 2),
            'is_locked' => (int) $row->generated_count > 0
                && (int) $row->generated_count === (int) $row->locked_count,
            'updated_at' => optional($row->updated_at ? Carbon::parse($row->updated_at) : null)
                ->format('d-m-Y H:i'),
        ];
    }

    private function medicalBonusByType(
        string $headerTable,
        string $typeColumn,
        string $periode,
        string $jenisPelayanan,
        Collection $niks,
        string $label
    ): Collection {
        if ($niks->isEmpty()) {
            return collect();
        }

        $rows = DB::table($headerTable.' as h')
            ->select([
                'h.id as source_id',
                'h.periode',
                'h.source_periode',
                'h.'.$typeColumn.' as jenis_pelayanan',
                'h.total_premi_medis_pool',
                'h.premi_medis_per_orang',
            ])
            ->where('h.periode', $periode)
            ->where('h.'.$typeColumn, $jenisPelayanan)
            ->where('h.is_locked', true)
            ->where('h.premi_medis_per_orang', '>', 0)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $totalPerPerson = round((float) $rows->sum('premi_medis_per_orang'), 2);

        if ($totalPerPerson <= 0) {
            return collect();
        }

        $info = [
            'total' => $totalPerPerson,
            'source_count' => $rows->pluck('source_id')->unique()->count(),
            'roles' => ["Premi Medis/Orang {$label}"],
            'sources' => $rows
                ->map(fn ($row) => [
                    'id' => (int) $row->source_id,
                    'periode' => $row->periode,
                    'source_periode' => $row->source_periode,
                    'jenis_pelayanan' => $row->jenis_pelayanan,
                    'role' => 'premi_medis_per_orang',
                    'role_label' => "Premi Medis/Orang {$label}",
                    'pool_total' => round((float) $row->total_premi_medis_pool, 2),
                    'total_received' => round((float) $row->premi_medis_per_orang, 2),
                ])
                ->values()
                ->all(),
        ];

        return $niks->mapWithKeys(fn ($nik) => [(string) $nik => $info]);
    }

    private function getCalculationMappings(
        string $jenisPelayanan,
        int $jnsPremiId,
        Collection $karcisTindakanIds
    ): Collection
    {
        $valueColumn = $jenisPelayanan === 'bpjs'
            ? 'mp.nilai_bpjs'
            : 'mp.nilai_umum';
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
                'mp.nilai_umum',
                'mp.nilai_bpjs',
                'mp.nilai_bersama_umum',
                'mp.nilai_bersama_bpjs',
                'jp.pembagi',
                DB::raw("{$jenisColumn} as jenis_mapping"),
                DB::raw("{$valueColumn} as nilai_mapping"),
                'jp.kode as kode_premi',
                'jp.jenis as nama_premi',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->where('mp.jnsPremi_id', $jnsPremiId)
            ->when(
                $jenisPelayanan === 'bpjs' && $karcisTindakanIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn(
                    'mp.jnsTindakan_id',
                    $karcisTindakanIds->all()
                )
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
                $this->sourceQuery(
                    $definition,
                    $jenisPelayanan,
                    $start,
                    $end,
                    $codes
                )->get()
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
        ?float $nilaiMappingOverride = null,
        string $suffix = '',
        ?string $jenisMappingOverride = null
    ): array {
        $nilaiMapping = $nilaiMappingOverride ?? (float) (
            $jenisPelayanan === 'bpjs'
                ? $mapping->nilai_bpjs
                : $mapping->nilai_umum
        );
        $totalBiaya = round((float) $items->sum('biaya_rawat'), 2);
        $jumlahData = $items->count();
        $jenisMapping = $jenisMappingOverride ?? $mapping->jenis_mapping;
        $dasarHitung = $jenisMapping === 'persen'
            ? $totalBiaya
            : $jumlahData;
        $hasil = $jenisMapping === 'persen'
            ? round($totalBiaya * ($nilaiMapping / 100), 2)
            : round($jumlahData * $nilaiMapping, 2);

        return [
            'mapping_premi_id' => (int) $mapping->mapping_premi_id,
            'jnsPremi_id' => (int) $mapping->jnsPremi_id,
            'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
            'kode_premi' => $mapping->kode_premi,
            'nama_premi' => trim($mapping->nama_premi.$suffix),
            'kode_jenis_tindakan' => $mapping->kode_jenis_tindakan,
            'nama_jenis_tindakan' => trim($mapping->nama_jenis_tindakan.$suffix),
            'jenis_mapping' => $jenisMapping,
            'nilai_mapping' => $nilaiMapping,
            'source_rules' => $this->sourceRulesForAction((int) $mapping->jnsTindakan_id, $sourceMappings),
            'jumlah_data' => $jumlahData,
            'jumlah_data_icu' => $items->where('is_icu', true)->count(),
            'jumlah_data_nicu' => $items->where('is_nicu', true)->count(),
            'total_biaya_rawat' => $totalBiaya,
            'dasar_hitung' => $dasarHitung,
            'hasil_mapping' => $hasil,
            'data_rawat' => $items->values()->all(),
        ];
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
        if ($doctorCodes->isEmpty() || $doctorActionIds->isEmpty() || $sourceType === 'bpjs_karcis') {
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

    private function plotingLabel(object $row): string
    {
        return trim(($row->kode_ploting ? $row->kode_ploting.' - ' : '').($row->nama_ploting ?? '-'));
    }
}
