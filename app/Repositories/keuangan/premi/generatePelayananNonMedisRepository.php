<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generateBhpModel;
use App\Models\dbSimrs\generateKamarModel;
use App\Models\dbSimrs\premiPelayananNonMedisDetailModel;
use App\Models\dbSimrs\premiPelayananNonMedisDistributionModel;
use App\Models\dbSimrs\premiPelayananNonMedisModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class generatePelayananNonMedisRepository
{
    private const SOURCE_TABLES = [
        [
            'table' => 'rawat_jl_pr',
            'source' => 'RAJAL',
            'provider' => 'pr',
        ],
        [
            'table' => 'rawat_inap_pr',
            'source' => 'RANAP',
            'provider' => 'pr',
        ],
        [
            'table' => 'rawat_jl_dr',
            'source' => 'RAJAL',
            'provider' => 'dr',
        ],
        [
            'table' => 'rawat_inap_dr',
            'source' => 'RANAP',
            'provider' => 'dr',
        ],
        [
            'table' => 'rawat_jl_drpr',
            'source' => 'RAJAL',
            'provider' => 'drpr',
        ],
        [
            'table' => 'rawat_inap_drpr',
            'source' => 'RANAP',
            'provider' => 'drpr',
        ],
    ];

    public function getDependencies(
        string $periode,
        string $jenisPelayanan,
        bool $forUpdate = false,
        ?int $generateBhpId = null,
        ?int $generateKamarId = null
    ): array {
        $bhpQuery = generateBhpModel::query()
            ->where('periode', $periode)
            ->where('jenis_bhp', $jenisPelayanan);
        $kamarQuery = generateKamarModel::query()
            ->where('periode', $periode)
            ->where('jenis_kamar', $jenisPelayanan);

        if ($generateBhpId) {
            $bhpQuery->whereKey($generateBhpId);
        } else {
            $bhpQuery
                ->orderByRaw("CASE WHEN nama_ploting = 'Non Medis' THEN 0 ELSE 1 END")
                ->orderBy('nama_ploting');
        }

        if ($generateKamarId) {
            $kamarQuery->whereKey($generateKamarId);
        } else {
            $kamarQuery
                ->orderByRaw("CASE WHEN nama_ploting = 'Non Medis' THEN 0 ELSE 1 END")
                ->orderBy('nama_ploting');
        }

        if ($forUpdate) {
            $bhpQuery->lockForUpdate();
            $kamarQuery->lockForUpdate();
        }

        return [
            'bhp' => $bhpQuery->first(),
            'kamar' => $kamarQuery->first(),
        ];
    }

    public function getDependencyOptions(string $periode, string $jenisPelayanan): array
    {
        return [
            'bhp' => generateBhpModel::query()
                ->where('periode', $periode)
                ->where('jenis_bhp', $jenisPelayanan)
                ->orderBy('nama_ploting')
                ->get(),
            'kamar' => generateKamarModel::query()
                ->where('periode', $periode)
                ->where('jenis_kamar', $jenisPelayanan)
                ->orderBy('nama_ploting')
                ->get(),
        ];
    }

    public function calculate(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId
    ): array {
        $karcisTindakanIds = $this->getKarcisTindakanIds();
        $mappings = $this->getCalculationMappings(
            $jenisPelayanan,
            $jnsPremiId,
            $karcisTindakanIds
        );
        $selectedPremi = $mappings->first();

        if ($mappings->isEmpty()) {
            return [
                'transactions' => collect(),
                'details' => collect(),
                'jnsPremi_id' => $jnsPremiId,
                'kode_premi' => null,
                'nama_premi' => null,
                'jumlah_transaksi' => 0,
                'jumlah_jenis_tindakan' => 0,
                'jumlah_mapping_premi' => 0,
                'total_biaya_rawat' => 0,
                'total_mapping_premi' => 0,
            ];
        }

        $transactions = $this->getTransactions(
            $periode,
            $jenisPelayanan,
            $mappings,
            $karcisTindakanIds
        );
        $transactionsByAction = $transactions->groupBy('jnsTindakan_id');

        $details = $mappings
            ->groupBy('mapping_premi_id')
            ->flatMap(function (Collection $rows) use ($transactionsByAction, $jenisPelayanan) {
                $mapping = $rows->first();
                $items = $transactionsByAction
                    ->get($mapping->jnsTindakan_id, collect())
                    ->values();

                if ($jenisPelayanan !== 'umum') {
                    return collect([
                        $this->makeDetail(
                            $mapping,
                            $items,
                            (float) $mapping->nilai_mapping,
                            (int) $mapping->mapping_premi_id
                        ),
                    ]);
                }

                [$karcisBpjsItems, $umumItems] = $items
                    ->partition(fn ($item) => $item->jenis_pelayanan_sumber === 'bpjs_karcis');
                $details = collect();

                if ($umumItems->isNotEmpty()) {
                    $details->push(
                        $this->makeDetail(
                            $mapping,
                            $umumItems->values(),
                            (float) $mapping->nilai_umum,
                            (int) $mapping->mapping_premi_id,
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
                            (float) $mapping->nilai_bpjs,
                            null,
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
        $pembagi = max(1, (int) ($selectedPremi?->pembagi ?? 1));

        return [
            'transactions' => $transactions,
            'details' => $details,
            'jnsPremi_id' => $jnsPremiId,
            'kode_premi' => $selectedPremi?->kode_premi,
            'nama_premi' => $selectedPremi?->nama_premi,
            'pembagi' => $pembagi,
            'jumlah_transaksi' => $transactions->count(),
            'jumlah_jenis_tindakan' => $transactions
                ->pluck('jnsTindakan_id')
                ->unique()
                ->count(),
            'jumlah_mapping_premi' => $details->count(),
            'total_biaya_rawat' => round((float) $transactions->sum('biaya_rawat'), 2),
            'total_mapping_premi' => round((float) $details->sum('hasil_mapping'), 2),
        ];
    }

    private function makeDetail(
        object $mapping,
        Collection $items,
        float $nilaiMapping,
        ?int $mappingPremiId,
        string $suffix = '',
        ?string $jenisMappingOverride = null
    ): array {
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
            'mapping_premi_id' => $mappingPremiId,
            'jnsPremi_id' => (int) $mapping->jnsPremi_id,
            'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
            'kode_premi' => $mapping->kode_premi,
            'nama_premi' => trim($mapping->nama_premi.$suffix),
            'kode_jenis_tindakan' => $mapping->kode_jenis_tindakan,
            'nama_jenis_tindakan' => trim($mapping->nama_jenis_tindakan.$suffix),
            'jenis_mapping' => $jenisMapping,
            'nilai_mapping' => $nilaiMapping,
            'jumlah_data' => $jumlahData,
            'total_biaya_rawat' => $totalBiaya,
            'dasar_hitung' => $dasarHitung,
            'hasil_mapping' => $hasil,
            'data_tindakan' => $items->map(fn ($item) => [
                'source_table' => $item->source_table,
                'sumber_tindakan' => $item->sumber_tindakan,
                'no_rawat' => $item->no_rawat,
                'tanggal' => $item->tanggal,
                'jam' => $item->jam,
                'kd_tindakan' => $item->kd_tindakan,
                'nm_tindakan' => $item->nm_tindakan,
                'kd_pj' => $item->kd_pj,
                'nama_penjamin' => $item->nama_penjamin,
                'kd_dokter' => $item->kd_dokter,
                'nip' => $item->nip,
                'jenis_pelayanan_sumber' => $item->jenis_pelayanan_sumber,
                'biaya_rawat' => (float) $item->biaya_rawat,
            ])->all(),
        ];
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

    public function getConfig(): object
    {
        $defaultPremiId = DB::table('master_jenis_premi')
            ->where('kode', 'PRM001')
            ->where('jenis', 'like', '%Pelayanan non medis%')
            ->value('id');

        $config = DB::table('premi_pelayanan_non_medis_config')->first();

        if ($config) {
            return $config;
        }

        $now = now();
        $payload = [
            'jnsPremi_id' => $defaultPremiId,
            'distribution_mode' => 'split_evenly',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_umum_id')) {
            $payload['jnsPremi_umum_id'] = $defaultPremiId;
        }

        if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_bpjs_id')) {
            $payload['jnsPremi_bpjs_id'] = $defaultPremiId;
        }

        $id = DB::table('premi_pelayanan_non_medis_config')->insertGetId($payload);

        return DB::table('premi_pelayanan_non_medis_config')->where('id', $id)->first();
    }

    public function saveConfig(
        int $jnsPremiUmumId,
        int $jnsPremiBpjsId,
        string $distributionMode,
        array $karcisTindakanIds
    ): object
    {
        return DB::transaction(function () use (
            $jnsPremiUmumId,
            $jnsPremiBpjsId,
            $distributionMode,
            $karcisTindakanIds
        ) {
            $config = $this->getConfig();
            $updates = [
                'jnsPremi_id' => $jnsPremiUmumId,
                'distribution_mode' => $distributionMode,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_umum_id')) {
                $updates['jnsPremi_umum_id'] = $jnsPremiUmumId;
            }

            if (Schema::hasColumn('premi_pelayanan_non_medis_config', 'jnsPremi_bpjs_id')) {
                $updates['jnsPremi_bpjs_id'] = $jnsPremiBpjsId;
            }

            DB::table('premi_pelayanan_non_medis_config')
                ->where('id', $config->id)
                ->update($updates);

            $this->replaceKarcisConfig($karcisTindakanIds);

            return DB::table('premi_pelayanan_non_medis_config')
                ->where('id', $config->id)
                ->first();
        });
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
        return DB::table('premi_pelayanan_non_medis_karcis_config')
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
        DB::table('premi_pelayanan_non_medis_karcis_config')->delete();

        if (empty($tindakanIds)) {
            return;
        }

        $now = now();
        DB::table('premi_pelayanan_non_medis_karcis_config')->insert(
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

    public function findPremi(int $jnsPremiId)
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
            ->get()
            ->unique(fn ($row) => (string) $row->nik)
            ->values();
    }

    private function getCalculationMappings(
        string $jenisPelayanan,
        int $jnsPremiId,
        Collection $karcisTindakanIds
    ): Collection {
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

    private function getTransactions(
        string $periode,
        string $jenisPelayanan,
        Collection $mappings,
        Collection $karcisTindakanIds
    ): Collection {
        $transactions = $this->collectTransactions(
            $periode,
            $jenisPelayanan,
            $mappings,
            $jenisPelayanan
        );

        if ($jenisPelayanan === 'umum' && $karcisTindakanIds->isNotEmpty()) {
            $karcisMappings = $mappings
                ->filter(fn ($mapping) => $karcisTindakanIds->contains(
                    (int) $mapping->jnsTindakan_id
                ))
                ->values();

            if ($karcisMappings->isNotEmpty()) {
                $transactions = $transactions->merge(
                    $this->collectTransactions(
                        $periode,
                        'bpjs',
                        $karcisMappings,
                        'bpjs_karcis'
                    )
                );
            }
        }

        return $transactions
            ->sortBy(fn ($row) => implode('|', [
                $row->tanggal,
                $row->jam,
                $row->no_rawat,
                $row->source_table,
                $row->kd_tindakan,
            ]))
            ->values();
    }

    private function collectTransactions(
        string $periode,
        string $filterJenisPelayanan,
        Collection $mappings,
        string $sourceType
    ): Collection {
        $range = PremiSourcePeriod::range($periode, $filterJenisPelayanan);
        $mappingBySource = $mappings
            ->groupBy(fn ($mapping) => $mapping->sumber_tindakan.'|'.$mapping->kd_tindakan);
        $codesBySource = $mappings
            ->groupBy('sumber_tindakan')
            ->map(fn (Collection $items) => $items->pluck('kd_tindakan')->unique()->values());
        $transactions = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            $codes = $codesBySource->get($definition['source'], collect());

            if ($codes->isEmpty()) {
                continue;
            }

            $rows = $this->sourceQuery(
                $definition,
                $filterJenisPelayanan,
                $range['start'],
                $range['end'],
                $codes
            )->get();

            foreach ($rows as $row) {
                $key = $definition['source'].'|'.$row->kd_tindakan;
                $actionMappings = $mappingBySource->get($key, collect())
                    ->unique('jnsTindakan_id');

                foreach ($actionMappings as $mapping) {
                    $rowCopy = clone $row;
                    $rowCopy->jnsTindakan_id = (int) $mapping->jnsTindakan_id;
                    $rowCopy->nm_tindakan = $mapping->nm_tindakan;
                    $rowCopy->biaya_rawat = round((float) $rowCopy->biaya_rawat, 2);
                    $rowCopy->jenis_pelayanan_sumber = $sourceType;
                    $transactions->push($rowCopy);
                }
            }
        }

        return $transactions;
    }

    private function sourceQuery(
        array $definition,
        string $jenisPelayanan,
        Carbon $start,
        Carbon $end,
        Collection $codes
    ) {
        $query = DB::connection('mysql_khanza')
            ->table($definition['table'].' as r')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'r.no_rawat')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->select([
                DB::raw("'{$definition['table']}' as source_table"),
                DB::raw("'{$definition['source']}' as sumber_tindakan"),
                'r.no_rawat',
                'r.tgl_perawatan as tanggal',
                'r.jam_rawat as jam',
                'r.kd_jenis_prw as kd_tindakan',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
                'r.biaya_rawat',
            ])
            ->selectRaw(
                in_array($definition['provider'], ['dr', 'drpr'], true)
                    ? 'r.kd_dokter as kd_dokter'
                    : 'null as kd_dokter'
            )
            ->selectRaw(
                in_array($definition['provider'], ['pr', 'drpr'], true)
                    ? 'r.nip as nip'
                    : 'null as nip'
            )
            ->where('r.tgl_perawatan', '>=', $start->toDateString())
            ->where('r.tgl_perawatan', '<', $end->toDateString())
            ->whereIn('r.kd_jenis_prw', $codes->all())
            ->where('r.biaya_rawat', '>', 0);

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

    public function getResults(
        ?string $periode = null,
        ?string $jenisPelayanan = null,
        ?int $jnsPremiId = null
    ): Collection {
        return premiPelayananNonMedisModel::query()
            ->with([
                'lockedBy:id,name',
                'generateBy:id,name',
                'generateBhp:id,kode_ploting,nama_ploting',
                'generateKamar:id,kode_ploting,nama_ploting',
                'jnsPremi:id,pembagi',
                'distributions' => fn ($query) => $query->orderBy('pegawai_name'),
            ])
            ->withCount('details')
            ->withCount('distributions')
            ->withSum('distributions as total_dibagikan', 'total_diterima')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when(
                $jenisPelayanan,
                fn ($query) => $query->where('jenis_pelayanan', $jenisPelayanan)
            )
            ->when($jnsPremiId, fn ($query) => $query->where('jnsPremi_id', $jnsPremiId))
            ->orderByDesc('periode')
            ->orderBy('jenis_pelayanan')
            ->orderBy('nama_premi')
            ->get();
    }

    public function findByPeriodAndType(
        string $periode,
        string $jenisPelayanan,
        int $jnsPremiId
    ): ?premiPelayananNonMedisModel {
        return premiPelayananNonMedisModel::query()
            ->with('lockedBy:id,name')
            ->with([
                'generateBhp:id,kode_ploting,nama_ploting',
                'generateKamar:id,kode_ploting,nama_ploting',
                'jnsPremi:id,pembagi',
            ])
            ->with([
                'details' => fn ($query) => $query
                    ->select([
                        'id',
                        'premi_pelayanan_non_medis_id',
                        'mapping_premi_id',
                        'jnsTindakan_id',
                        'kode_jenis_tindakan',
                        'nama_jenis_tindakan',
                        'jenis_mapping',
                        'nilai_mapping',
                        'jumlah_data',
                        'total_biaya_rawat',
                        'dasar_hitung',
                        'hasil_mapping',
                        'data_tindakan',
                    ])
                    ->orderBy('nama_jenis_tindakan'),
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
    ): ?premiPelayananNonMedisModel {
        return premiPelayananNonMedisModel::query()
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
        array $dependencies,
        array $calculation
    ): premiPelayananNonMedisModel {
        $totalBhp = (float) $dependencies['bhp']->total_bhp;
        $totalKamar = (float) $dependencies['kamar']->total_lama_inap;
        $totalMapping = (float) $calculation['total_mapping_premi'];
        $pembagi = max(1, (int) ($calculation['pembagi'] ?? 1));

        return premiPelayananNonMedisModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_pelayanan' => $jenisPelayanan,
                'jnsPremi_id' => $jnsPremiId,
            ],
            [
                'kode_premi' => $calculation['kode_premi'],
                'nama_premi' => $calculation['nama_premi'],
                'generate_bhp_id' => $dependencies['bhp']->id,
                'generate_kamar_inap_id' => $dependencies['kamar']->id,
                'jumlah_transaksi' => $calculation['jumlah_transaksi'],
                'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan'],
                'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi'],
                'total_biaya_rawat' => $calculation['total_biaya_rawat'],
                'total_mapping_premi' => $totalMapping,
                'total_bhp' => $totalBhp,
                'total_kamar_inap' => $totalKamar,
                'total_final' => round(($totalMapping + $totalBhp + $totalKamar) / $pembagi, 2),
                'generate_by' => Auth::id(),
            ]
        );
    }

    public function replaceDetails(
        premiPelayananNonMedisModel $header,
        Collection $details
    ): void {
        $header->details()->delete();
        $now = now();

        $details
            ->map(fn (array $detail) => [
                ...$detail,
                'premi_pelayanan_non_medis_id' => $header->id,
                'data_tindakan' => json_encode(
                    $detail['data_tindakan'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(100)
            ->each(fn (Collection $chunk) => premiPelayananNonMedisDetailModel::query()
                ->insert($chunk->all()));
    }

    public function replaceDistributions(
        premiPelayananNonMedisModel $header,
        Collection $distributions
    ): void {
        $header->distributions()->delete();

        if ($distributions->isEmpty()) {
            return;
        }

        $now = now();
        $distributions
            ->unique(fn (array $distribution) => (string) $distribution['nik'])
            ->map(fn (array $distribution) => [
                ...$distribution,
                'premi_pelayanan_non_medis_id' => $header->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(100)
            ->each(fn (Collection $chunk) => premiPelayananNonMedisDistributionModel::query()
                ->insert($chunk->all()));
    }

    public function findWithDetails(int $id): ?premiPelayananNonMedisModel
    {
        return premiPelayananNonMedisModel::query()
            ->with([
                'lockedBy:id,name',
                'generateBy:id,name',
                'generateBhp:id,kode_ploting,nama_ploting',
                'generateKamar:id,kode_ploting,nama_ploting',
                'jnsPremi:id,pembagi',
            ])
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('nama_jenis_tindakan')
                    ->orderBy('nama_premi'),
                'distributions' => fn ($query) => $query->orderBy('pegawai_name'),
            ])
            ->find($id);
    }

    public function findForUpdate(int $id): ?premiPelayananNonMedisModel
    {
        return premiPelayananNonMedisModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function updateLock(
        premiPelayananNonMedisModel $header,
        bool $isLocked,
        ?int $userId = null
    ): premiPelayananNonMedisModel {
        DB::table('premi_pelayanan_non_medis')
            ->where('id', $header->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return premiPelayananNonMedisModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($header->id);
    }
}
