<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateApotekConfigModel;
use App\Models\dbSimrs\generateApotekModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateApotekRepository
{
    public function getResults(?string $periode = null, ?string $jenisApotek = null): Collection
    {
        return generateApotekModel::query()
            ->with(['jenisTindakan:id,kode,jenis', 'lockedBy:id,name', 'generateBy:id,name'])
            ->withCount(['details', 'recipients'])
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisApotek, fn ($query) => $query->where('jenis_apotek', $jenisApotek))
            ->orderByDesc('periode')
            ->orderBy('jenis_apotek')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisApotek): array
    {
        $rows = $periode
            ? generateApotekModel::query()
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
            'total_qty' => $rows->sum('total_qty'),
            'grand_total' => $rows->sum('grand_total'),
            'total_jasa_farmasi_pool' => $rows->sum('total_jasa_farmasi_pool'),
            'total_formula_31' => $rows->sum('total_formula_31'),
            'total_formula_7' => $rows->sum('total_formula_7'),
            'total_formula_12' => $rows->sum('total_formula_12'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
        ];
    }

    public function getConfig(string $jenisApotek): generateApotekConfigModel
    {
        $this->ensureDefaultConfigs();

        $config = generateApotekConfigModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'mappings.jenisTindakan:id,kode,jenis',
                'pegawai',
            ])
            ->where('jenis_apotek', $jenisApotek)
            ->firstOrFail();

        $this->ensureConfigMappings($config);

        return $config->fresh([
            'jenisTindakan:id,kode,jenis',
            'mappings.jenisTindakan:id,kode,jenis',
            'pegawai',
        ]);
    }

    public function saveConfig(
        string $jenisApotek,
        array $payload,
        array $mappingIds,
        array $recipients
    ): generateApotekConfigModel {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisApotek, $payload, $mappingIds, $recipients) {
            $config = generateApotekConfigModel::query()
                ->where('jenis_apotek', $jenisApotek)
                ->lockForUpdate()
                ->firstOrFail();

            $mappingIds = collect($mappingIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $payload['jnsTindakan_id'] = $mappingIds->first();

            $config->update($payload);
            $config->mappings()->delete();
            $config->pegawai()->delete();

            $now = now();
            if ($mappingIds->isNotEmpty()) {
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
                'mappings.jenisTindakan:id,kode,jenis',
                'pegawai',
            ]);
        });
    }

    public function mappingOptions(?string $keyword = null): Collection
    {
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

    public function findExistingForUpdate(string $periode, string $jenisApotek): ?generateApotekModel
    {
        return generateApotekModel::query()
            ->where('periode', $periode)
            ->where('jenis_apotek', $jenisApotek)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisApotek, array $calculation): generateApotekModel
    {
        $source = $calculation['source'];
        $mapping = $source['mapping'];
        $primaryMapping = $mapping['items'][0] ?? null;
        $pools = $calculation['pools'];

        $result = generateApotekModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_apotek' => $jenisApotek,
            ],
            [
                'source_periode' => $source['source_periode'],
                'source_tgl_awal' => $source['source_tgl_awal'],
                'source_tgl_akhir' => $source['source_tgl_akhir'],
                'jnsTindakan_id' => $primaryMapping['id'] ?? null,
                'kode_jenis_tindakan' => $primaryMapping['kode'] ?? null,
                'nama_jenis_tindakan' => $mapping['label'] ?? ($primaryMapping['jenis'] ?? null),
                'jumlah_data_sumber' => $source['jumlah_data_sumber'],
                'jumlah_pasien_sumber' => $source['jumlah_pasien_sumber'],
                'jumlah_obat_sumber' => $source['jumlah_obat_sumber'],
                'jumlah_data_mapping' => $source['jumlah_data_mapping'],
                'jumlah_pasien' => $source['jumlah_pasien'],
                'jumlah_obat' => $source['jumlah_obat'],
                'total_qty' => $source['total_qty'],
                'tarif_per_item' => $source['tarif_per_item'],
                'grand_total' => $source['grand_total'],
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
                'lockedBy:id,name',
                'generateBy:id,name',
                'details' => fn ($query) => $query
                    ->orderBy('tanggal')
                    ->orderBy('jam')
                    ->orderBy('no_rawat')
                    ->orderBy('kode_barang'),
                'recipients' => fn ($query) => $query
                    ->orderByRaw("FIELD(role, 'penerima_31', 'penerima_7', 'penerima_12')")
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

    private function cleanTime(?string $time): ?string
    {
        $time = trim((string) $time);

        return $time === '' ? null : $time;
    }

    private function ensureDefaultConfigs(): void
    {
        foreach (['umum', 'bpjs'] as $jenis) {
            $defaultSourceMode = $jenis === 'bpjs'
                ? PremiSourcePeriod::MODE_PREVIOUS
                : PremiSourcePeriod::MODE_CURRENT;
            $config = generateApotekConfigModel::query()->firstOrCreate(
                ['jenis_apotek' => $jenis],
                [
                    'jnsTindakan_id' => null,
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
}
