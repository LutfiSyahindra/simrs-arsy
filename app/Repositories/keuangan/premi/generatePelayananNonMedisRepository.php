<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\generateBhpModel;
use App\Models\dbSimrs\generateKamarModel;
use App\Models\dbSimrs\premiPelayananNonMedisDetailModel;
use App\Models\dbSimrs\premiPelayananNonMedisModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        bool $forUpdate = false
    ): array {
        $bhpQuery = generateBhpModel::query()
            ->where('periode', $periode)
            ->where('jenis_bhp', $jenisPelayanan);
        $kamarQuery = generateKamarModel::query()
            ->where('periode', $periode)
            ->where('jenis_kamar', $jenisPelayanan);

        if ($forUpdate) {
            $bhpQuery->lockForUpdate();
            $kamarQuery->lockForUpdate();
        }

        return [
            'bhp' => $bhpQuery->first(),
            'kamar' => $kamarQuery->first(),
        ];
    }

    public function calculate(string $periode, string $jenisPelayanan): array
    {
        $mappings = $this->getCalculationMappings($jenisPelayanan);

        if ($mappings->isEmpty()) {
            return [
                'transactions' => collect(),
                'details' => collect(),
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
            $mappings
        );
        $transactionsByAction = $transactions->groupBy('jnsTindakan_id');

        $details = $mappings
            ->groupBy('mapping_premi_id')
            ->map(function (Collection $rows) use ($transactionsByAction) {
                $mapping = $rows->first();
                $items = $transactionsByAction
                    ->get($mapping->jnsTindakan_id, collect())
                    ->values();
                $totalBiaya = round((float) $items->sum('biaya_rawat'), 2);
                $jumlahData = $items->count();
                $dasarHitung = $mapping->jenis_mapping === 'persen'
                    ? $totalBiaya
                    : $jumlahData;
                $hasil = $mapping->jenis_mapping === 'persen'
                    ? round($totalBiaya * ((float) $mapping->nilai_mapping / 100), 2)
                    : round($jumlahData * (float) $mapping->nilai_mapping, 2);

                return [
                    'mapping_premi_id' => (int) $mapping->mapping_premi_id,
                    'jnsPremi_id' => (int) $mapping->jnsPremi_id,
                    'jnsTindakan_id' => (int) $mapping->jnsTindakan_id,
                    'kode_premi' => $mapping->kode_premi,
                    'nama_premi' => $mapping->nama_premi,
                    'kode_jenis_tindakan' => $mapping->kode_jenis_tindakan,
                    'nama_jenis_tindakan' => $mapping->nama_jenis_tindakan,
                    'jenis_mapping' => $mapping->jenis_mapping,
                    'nilai_mapping' => (float) $mapping->nilai_mapping,
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
                        'biaya_rawat' => (float) $item->biaya_rawat,
                    ])->all(),
                ];
            })
            ->filter(fn (array $detail) => $detail['jumlah_data'] > 0)
            ->sortBy('nama_jenis_tindakan')
            ->values();

        return [
            'transactions' => $transactions,
            'details' => $details,
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

    private function getCalculationMappings(string $jenisPelayanan): Collection
    {
        $valueColumn = $jenisPelayanan === 'bpjs'
            ? 'mp.nilai_bpjs'
            : 'mp.nilai_umum';

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
                'mp.jenis as jenis_mapping',
                DB::raw("{$valueColumn} as nilai_mapping"),
                'jp.kode as kode_premi',
                'jp.jenis as nama_premi',
                'jt.kode as kode_jenis_tindakan',
                'jt.jenis as nama_jenis_tindakan',
            ])
            ->orderBy('mt.sumber_tindakan')
            ->orderBy('mt.kd_tindakan')
            ->get();
    }

    private function getTransactions(
        string $periode,
        string $jenisPelayanan,
        Collection $mappings
    ): Collection {
        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
        $end = $start->copy()->addMonth();
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
                $jenisPelayanan,
                $start,
                $end,
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
                    $transactions->push($rowCopy);
                }
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
        ?string $jenisPelayanan = null
    ): Collection {
        return premiPelayananNonMedisModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when(
                $jenisPelayanan,
                fn ($query) => $query->where('jenis_pelayanan', $jenisPelayanan)
            )
            ->orderByDesc('periode')
            ->orderBy('jenis_pelayanan')
            ->get();
    }

    public function findByPeriodAndType(
        string $periode,
        string $jenisPelayanan
    ): ?premiPelayananNonMedisModel {
        return premiPelayananNonMedisModel::query()
            ->with('lockedBy:id,name')
            ->where('periode', $periode)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->first();
    }

    public function findByPeriodAndTypeForUpdate(
        string $periode,
        string $jenisPelayanan
    ): ?premiPelayananNonMedisModel {
        return premiPelayananNonMedisModel::query()
            ->where('periode', $periode)
            ->where('jenis_pelayanan', $jenisPelayanan)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(
        string $periode,
        string $jenisPelayanan,
        array $dependencies,
        array $calculation
    ): premiPelayananNonMedisModel {
        $totalBhp = (float) $dependencies['bhp']->total_bhp;
        $totalKamar = (float) $dependencies['kamar']->total_lama_inap;
        $totalMapping = (float) $calculation['total_mapping_premi'];

        return premiPelayananNonMedisModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_pelayanan' => $jenisPelayanan,
            ],
            [
                'generate_bhp_id' => $dependencies['bhp']->id,
                'generate_kamar_inap_id' => $dependencies['kamar']->id,
                'jumlah_transaksi' => $calculation['jumlah_transaksi'],
                'jumlah_jenis_tindakan' => $calculation['jumlah_jenis_tindakan'],
                'jumlah_mapping_premi' => $calculation['jumlah_mapping_premi'],
                'total_biaya_rawat' => $calculation['total_biaya_rawat'],
                'total_mapping_premi' => $totalMapping,
                'total_bhp' => $totalBhp,
                'total_kamar_inap' => $totalKamar,
                'total_final' => round($totalMapping + $totalBhp + $totalKamar, 2),
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

    public function findWithDetails(int $id): ?premiPelayananNonMedisModel
    {
        return premiPelayananNonMedisModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('nama_jenis_tindakan')
                    ->orderBy('nama_premi'),
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
