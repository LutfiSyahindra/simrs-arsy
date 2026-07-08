<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generatePremiDokterModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class generatePremiDokterRepository
{
    public const TYPE_VISITE = 'visite';

    public const CATEGORY_UMUM = 'umum';

    public const CATEGORY_SPESIALIS_65 = 'spesialis_65';

    public const CATEGORY_SPESIALIS_80 = 'spesialis_80';

    public const SOURCE_PERIOD_CURRENT = 'current';

    public const SOURCE_PERIOD_PREVIOUS = 'previous';

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

    public function getResults(?string $periode = null, ?string $jenisPelayanan = null): Collection
    {
        return generatePremiDokterModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->where('jenis_premi_dokter', self::TYPE_VISITE)
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisPelayanan, fn ($query) => $query->where('jenis_pelayanan', $jenisPelayanan))
            ->orderByDesc('periode')
            ->orderBy('jenis_pelayanan')
            ->get();
    }

    public function getConfig(): object
    {
        $config = DB::table('generate_premi_dokter_configs')->first();

        if ($config) {
            return $config;
        }

        $id = DB::table('generate_premi_dokter_configs')->insertGetId([
            'visite_umum_percent' => 50,
            'visite_bpjs_percent' => 50,
            'visite_bpjs_nominal' => 0,
            'source_period_mode' => self::SOURCE_PERIOD_CURRENT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('generate_premi_dokter_configs')->where('id', $id)->first();
    }

    public function saveConfig(
        float $visiteUmumPercent,
        float $visiteBpjsPercent,
        int $visiteBpjsNominal,
        string $sourcePeriodMode,
        array $jnsTindakanIds,
        array $doctorRows
    ): object {
        return DB::transaction(function () use (
            $visiteUmumPercent,
            $visiteBpjsPercent,
            $visiteBpjsNominal,
            $sourcePeriodMode,
            $jnsTindakanIds,
            $doctorRows
        ) {
            $config = $this->getConfig();
            $now = now();
            $sourcePeriodMode = $this->normalizeSourcePeriodMode($sourcePeriodMode);

            DB::table('generate_premi_dokter_configs')
                ->where('id', $config->id)
                ->update([
                    'visite_umum_percent' => $visiteUmumPercent,
                    'visite_bpjs_percent' => $visiteBpjsPercent,
                    'visite_bpjs_nominal' => $visiteBpjsNominal,
                    'source_period_mode' => $sourcePeriodMode,
                    'updated_at' => $now,
                ]);

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
                ->unique('kd_dokter')
                ->values();

            if ($doctorRows->isNotEmpty()) {
                DB::table('generate_premi_dokter_config_doctor')->insert($doctorRows->all());
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

    public function getConfigDoctors(?int $configId = null): Collection
    {
        $configId ??= (int) $this->getConfig()->id;

        return DB::table('generate_premi_dokter_config_doctor')
            ->where('config_id', $configId)
            ->orderByRaw("case kategori when 'umum' then 0 when 'spesialis_65' then 1 when 'spesialis_80' then 2 else 3 end")
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

    public function calculate(string $periode, string $jenisPelayanan, object $config): array
    {
        $sourcePeriodMode = $jenisPelayanan === 'bpjs'
            ? $this->normalizeSourcePeriodMode($config->source_period_mode ?? null)
            : self::SOURCE_PERIOD_CURRENT;
        $sourcePeriode = $this->sourcePeriod($periode, $sourcePeriodMode);
        $range = $this->periodRange($sourcePeriode);
        $jenisTindakan = $this->getConfigTindakan((int) $config->id);
        $mappingTindakan = $this->getConfiguredMappingTindakan((int) $config->id);
        $allDoctorConfig = $this->getConfigDoctors((int) $config->id);
        $doctorConfig = $jenisPelayanan === 'bpjs'
            ? $allDoctorConfig->where('kategori', self::CATEGORY_UMUM)->values()
            : $allDoctorConfig->values();
        $doctorByCode = $doctorConfig->keyBy('kd_dokter');
        $specialistCodes = $jenisPelayanan === 'bpjs'
            ? $allDoctorConfig
                ->whereIn('kategori', [self::CATEGORY_SPESIALIS_65, self::CATEGORY_SPESIALIS_80])
                ->pluck('kd_dokter')
                ->map(fn ($code) => (string) $code)
                ->values()
            : collect();
        $rows = $this->collectVisiteRows(
            $jenisPelayanan,
            $range['start'],
            $range['end'],
            $mappingTindakan
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

    public function saveResult(
        string $periode,
        string $jenisPelayanan,
        object $config,
        array $calculation
    ): generatePremiDokterModel {
        return DB::transaction(function () use ($periode, $jenisPelayanan, $config, $calculation) {
            $existing = $this->findByPeriodAndTypeForUpdate($periode, $jenisPelayanan, self::TYPE_VISITE);

            if ($existing?->is_locked) {
                throw ValidationException::withMessages([
                    'periode' => "Premi dokter visite {$jenisPelayanan} periode {$periode} sudah dikunci.",
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
                    'jenis_premi_dokter' => self::TYPE_VISITE,
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
        Collection $mappingTindakan
    ): Collection {
        if ($mappingTindakan->isEmpty()) {
            return collect();
        }

        $mappingBySourceCode = $mappingTindakan
            ->groupBy(fn ($item) => $item->sumber_tindakan.'|'.$item->kd_tindakan);
        $codesBySource = $mappingTindakan
            ->groupBy('sumber_tindakan')
            ->map(fn (Collection $items) => $items->pluck('kd_tindakan')->unique()->values());
        $rows = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            $codes = $codesBySource->get($definition['source'], collect());

            if ($codes->isEmpty()) {
                continue;
            }

            $rawRows = $this->sourceQuery($definition, $jenisPelayanan, $start, $end, $codes)->get();

            foreach ($rawRows as $row) {
                $key = $row->sumber_tindakan.'|'.$row->kd_tindakan;
                $matches = $mappingBySourceCode->get($key, collect());

                foreach ($matches as $mapping) {
                    if (! filled($row->kd_dokter)) {
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
        Collection $codes
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
