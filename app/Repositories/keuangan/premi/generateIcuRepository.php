<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateIcuConfigModel;
use App\Models\dbSimrs\generateIcuModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateIcuRepository
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

    public function getResults(?string $periode = null, ?string $jenisIcu = null): Collection
    {
        return generateIcuModel::query()
            ->with(['jenisTindakan:id,kode,jenis', 'lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisIcu, fn ($query) => $query->where('jenis_icu', $jenisIcu))
            ->orderByDesc('periode')
            ->orderBy('jenis_icu')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisIcu): array
    {
        $rows = $periode
            ? generateIcuModel::query()
                ->where('periode', $periode)
                ->where('jenis_icu', $jenisIcu)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_pasien_sumber' => $rows->sum('jumlah_pasien_sumber'),
            'jumlah_pasien_icu' => $rows->sum('jumlah_pasien_icu'),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'jumlah_tindakan_icu' => $rows->sum('jumlah_tindakan_icu'),
            'jumlah_tindakan_kritikal' => $rows->sum('jumlah_tindakan_kritikal'),
            'grand_total' => $rows->sum('grand_total'),
            'total_perawat_icu' => $rows->sum('total_perawat_icu'),
            'total_perawat_icu_reguler' => $rows->sum('total_perawat_icu_reguler'),
            'total_pegawai_icu_khusus' => $rows->sum('total_pegawai_icu_khusus'),
            'total_premi_medis_pool' => $rows->sum('total_premi_medis_pool'),
            'premi_medis_per_orang' => $rows->sum('premi_medis_per_orang'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
        ];
    }

    public function getConfig(string $jenisIcu): generateIcuConfigModel
    {
        $this->ensureDefaultConfigs();

        $config = generateIcuConfigModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'tindakan.jenisTindakan:id,kode,jenis',
                'pegawai',
            ])
            ->where('jenis_icu', $jenisIcu)
            ->firstOrFail();

        $this->ensureConfigMappings($config);

        return $config->fresh([
            'jenisTindakan:id,kode,jenis',
            'tindakan.jenisTindakan:id,kode,jenis',
            'pegawai',
        ]);
    }

    public function saveConfig(
        string $jenisIcu,
        array $payload,
        array $mappingIds,
        array $recipients
    ): generateIcuConfigModel {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisIcu, $payload, $mappingIds, $recipients) {
            $config = generateIcuConfigModel::query()
                ->where('jenis_icu', $jenisIcu)
                ->lockForUpdate()
                ->firstOrFail();

            $mappingIds = collect($mappingIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $payload['jnsTindakan_id'] = $mappingIds->first();

            $config->update($payload);
            $config->tindakan()->delete();
            $config->pegawai()->delete();

            $now = now();
            if ($mappingIds->isNotEmpty()) {
                DB::table('generate_icu_config_tindakan')->insert(
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
                DB::table('generate_icu_config_pegawai')->insert($pegawaiRows);
            }

            return $config->fresh([
                'jenisTindakan:id,kode,jenis',
                'tindakan.jenisTindakan:id,kode,jenis',
                'pegawai',
            ]);
        });
    }

    public function mappingOptions(?string $keyword = null): Collection
    {
        return DB::table('master_jenis_tindakan as jt')
            ->leftJoin('mapping_tindakan as mt', 'mt.jnsTindakan_id', '=', 'jt.id')
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

    public function criticalActionOptions(?string $keyword = null): Collection
    {
        $keyword = trim((string) $keyword);

        return DB::connection('mysql_khanza')
            ->table('jns_perawatan_inap')
            ->select('nm_perawatan')
            ->when(
                $keyword !== '',
                fn ($query) => $query->where('nm_perawatan', 'like', "%{$keyword}%"),
                fn ($query) => $query->where(function ($search) {
                    $search
                        ->where('nm_perawatan', 'like', '%ICU%')
                        ->orWhere('nm_perawatan', 'like', '%KRITIKAL%');
                })
            )
            ->whereNotNull('nm_perawatan')
            ->where('nm_perawatan', '!=', '')
            ->where('status', '1')
            ->groupBy('nm_perawatan')
            ->orderBy('nm_perawatan')
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

    public function getSourceData(string $periode, string $jenisIcu, array $config): array
    {
        $range = PremiSourcePeriod::range($periode, $jenisIcu);
        $sourcePeriode = PremiSourcePeriod::resolve($periode, $jenisIcu);
        $sourceStart = $range['start'];
        $sourceEnd = $range['end'];
        $sourceEndInclusive = $sourceEnd->copy()->subDay();
        $admissions = $this->eligibleAdmissions($jenisIcu, $sourceStart, $sourceEnd);
        $icuRanges = $admissions->isEmpty()
            ? collect()
            : $this->icuRanges($admissions->keys()->values());
        $mappingIds = $config['jnsTindakan_ids'] ?? [];
        $mappings = $this->selectedMappings($mappingIds);
        $mappingByKey = $mappings->keyBy(fn ($row) => $row->sumber_tindakan.'|'.$row->kd_tindakan);
        $codesBySource = $mappings
            ->groupBy('sumber_tindakan')
            ->map(fn (Collection $rows) => $rows->pluck('kd_tindakan')->unique()->values());
        $criticalActionNames = $this->criticalActionNames($config);
        $details = collect();

        if ($admissions->isNotEmpty() && $icuRanges->isNotEmpty()) {
            foreach (self::SOURCE_TABLES as $definition) {
                $codes = $codesBySource->get($definition['source'], collect());
                $needsCriticalLookup = $definition['source'] === 'RANAP' && $criticalActionNames->isNotEmpty();

                if ($codes->isEmpty() && ! $needsCriticalLookup) {
                    continue;
                }

                foreach ($admissions->keys()->chunk(600) as $noRawatChunk) {
                    $rows = $this->sourceQuery(
                        $definition,
                        $noRawatChunk->values(),
                        $codes,
                        $criticalActionNames
                    )->get();

                    foreach ($rows as $row) {
                        $patientIcuRanges = $icuRanges->get($row->no_rawat, collect());

                        if ($patientIcuRanges->isEmpty()) {
                            continue;
                        }

                        $sourceKey = $row->sumber_tindakan.'|'.$row->kd_tindakan;
                        $mapping = $mappingByKey->get($sourceKey);
                        $rangeMatch = $this->matchingIcuRange($row, $patientIcuRanges);
                        $isCritical = $this->isCriticalAction($row->nm_tindakan, $criticalActionNames)
                            && $row->sumber_tindakan === 'RANAP';

                        if (! (($mapping && $rangeMatch) || $isCritical)) {
                            continue;
                        }

                        $contextRange = $rangeMatch ?: $patientIcuRanges->first();
                        $admission = $admissions->get($row->no_rawat);
                        $details->push($this->detailPayload(
                            $row,
                            $mapping,
                            $admission,
                            $contextRange,
                            (bool) $rangeMatch,
                            $isCritical
                        ));
                    }
                }
            }
        }

        $details = $details
            ->unique(fn ($row) => implode('|', [
                $row['source_table'],
                $row['no_rawat'],
                $row['tanggal'],
                $row['jam'],
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

        return [
            'source_periode' => $sourcePeriode,
            'source_tgl_awal' => $sourceStart->toDateString(),
            'source_tgl_akhir' => $sourceEndInclusive->toDateString(),
            'jumlah_pasien_sumber' => $admissions->count(),
            'jumlah_pasien_icu' => $icuRanges->keys()->count(),
            'jumlah_pasien' => $details->pluck('no_rawat')->unique()->count(),
            'jumlah_tindakan' => $details->count(),
            'jumlah_tindakan_icu' => $details->where('is_in_icu_range', true)->count(),
            'jumlah_tindakan_kritikal' => $details->where('is_critical_action', true)->count(),
            'grand_total' => (int) round($details->sum('biaya_rawat')),
            'mapping' => $this->mappingSummary($mappingIds, $mappings),
            'details' => $details,
        ];
    }

    public function findExistingForUpdate(string $periode, string $jenisIcu): ?generateIcuModel
    {
        return generateIcuModel::query()
            ->where('periode', $periode)
            ->where('jenis_icu', $jenisIcu)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisIcu, array $calculation): generateIcuModel
    {
        $source = $calculation['source'];
        $mapping = $source['mapping'];
        $primaryMapping = $mapping['items'][0] ?? null;
        $pools = $calculation['pools'];

        $result = generateIcuModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_icu' => $jenisIcu,
            ],
            [
                'source_periode' => $source['source_periode'],
                'source_tgl_awal' => $source['source_tgl_awal'],
                'source_tgl_akhir' => $source['source_tgl_akhir'],
                'jnsTindakan_id' => $primaryMapping['id'] ?? null,
                'kode_jenis_tindakan' => $primaryMapping['kode'] ?? null,
                'nama_jenis_tindakan' => $mapping['label'] ?? ($primaryMapping['jenis'] ?? null),
                'jumlah_pasien_sumber' => $source['jumlah_pasien_sumber'],
                'jumlah_pasien_icu' => $source['jumlah_pasien_icu'],
                'jumlah_pasien' => $source['jumlah_pasien'],
                'jumlah_tindakan' => $source['jumlah_tindakan'],
                'jumlah_tindakan_icu' => $source['jumlah_tindakan_icu'],
                'jumlah_tindakan_kritikal' => $source['jumlah_tindakan_kritikal'],
                'grand_total' => $source['grand_total'],
                'total_perawat_icu' => $pools['perawat_icu'],
                'total_perawat_icu_reguler' => $pools['perawat_icu_reguler'],
                'total_pegawai_icu_khusus' => $pools['pegawai_icu_khusus'],
                'total_premi_medis_pool' => $pools['premi_medis_pool'],
                'premi_medis_per_orang' => $pools['premi_medis_per_orang'],
                'total_premi_bersama' => $pools['premi_bersama'],
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
                'generate_icu_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(500)
            ->each(fn (Collection $chunk) => DB::table('generate_icu_detail')->insert($chunk->all()));

        collect($calculation['recipients'])
            ->map(fn (array $recipient) => [
                ...$recipient,
                'generate_icu_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(200)
            ->each(fn (Collection $chunk) => DB::table('generate_icu_recipient')->insert($chunk->all()));

        return $result->fresh(['details', 'recipients', 'jenisTindakan:id,kode,jenis', 'lockedBy:id,name', 'generateBy:id,name']);
    }

    public function findForUpdate(int $id): ?generateIcuModel
    {
        return generateIcuModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateIcuModel
    {
        return generateIcuModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'lockedBy:id,name',
                'generateBy:id,name',
                'details' => fn ($query) => $query
                    ->orderBy('tanggal')
                    ->orderBy('jam')
                    ->orderBy('no_rawat')
                    ->orderBy('source_table'),
                'recipients' => fn ($query) => $query
                    ->orderByRaw("FIELD(role, 'perawat_icu', 'pegawai_icu_khusus')")
                    ->orderBy('pegawai_name'),
            ])
            ->find($id);
    }

    public function updateLock(
        generateIcuModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateIcuModel {
        DB::table('generate_icu')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateIcuModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateIcuModel $result): void
    {
        $result->delete();
    }

    private function eligibleAdmissions(string $jenisIcu, Carbon $start, Carbon $end): Collection
    {
        $query = DB::connection('mysql_khanza')
            ->table('kamar_inap as ki')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'ki.no_rawat')
            ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->select([
                'ki.no_rawat',
                'rp.no_rkm_medis',
                'ps.nm_pasien',
                'rp.kd_pj',
                'pj.png_jawab as nama_penjamin',
            ])
            ->where('ki.tgl_masuk', '>=', $start->toDateString())
            ->where('ki.tgl_masuk', '<', $end->toDateString())
            ->when(
                $jenisIcu === 'bpjs',
                fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                fn ($query) => $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
            )
            ->groupBy('ki.no_rawat', 'rp.no_rkm_medis', 'ps.nm_pasien', 'rp.kd_pj', 'pj.png_jawab');

        return $query->get()->keyBy('no_rawat');
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

    private function sourceQuery(
        array $definition,
        Collection $noRawats,
        Collection $codes,
        Collection $criticalActionNames
    ) {
        $hasDoctor = in_array($definition['provider'], ['dr', 'drpr'], true);
        $hasPetugas = in_array($definition['provider'], ['pr', 'drpr'], true);
        $hasCodes = $codes->isNotEmpty();
        $hasCritical = $definition['source'] === 'RANAP' && $criticalActionNames->isNotEmpty();

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
            ->whereIn('r.no_rawat', $noRawats->values()->all())
            ->where('r.biaya_rawat', '>', 0)
            ->where(function ($where) use ($codes, $criticalActionNames, $hasCodes, $hasCritical) {
                if ($hasCodes) {
                    $where->whereIn('r.kd_jenis_prw', $codes->values()->all());
                }

                if ($hasCritical) {
                    $hasCodes
                        ? $where->orWhereIn('jp.nm_perawatan', $criticalActionNames->all())
                        : $where->whereIn('jp.nm_perawatan', $criticalActionNames->all());
                }
            });

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
            ->whereIn('mt.jnsTindakan_id', $jnsTindakanIds->all())
            ->get();
    }

    private function matchingIcuRange(object $row, Collection $ranges): ?object
    {
        $actionAt = $this->actionDateTime($row->tanggal, $row->jam);

        return $ranges->first(function ($range) use ($actionAt) {
            return $actionAt->betweenIncluded(
                $this->rangeStart($range),
                $this->rangeEnd($range)
            );
        });
    }

    private function detailPayload(
        object $row,
        ?object $mapping,
        ?object $admission,
        object $icuRange,
        bool $isInIcuRange,
        bool $isCritical
    ): array {
        return [
            'mapping_tindakan_id' => $mapping?->id,
            'jnsTindakan_id' => $mapping?->jnsTindakan_id,
            'source_table' => $row->source_table,
            'sumber_tindakan' => $row->sumber_tindakan,
            'no_rawat' => $row->no_rawat,
            'no_rkm_medis' => $admission?->no_rkm_medis ?? $row->no_rkm_medis,
            'nm_pasien' => $admission?->nm_pasien ?? $row->nm_pasien,
            'kd_pj' => $admission?->kd_pj ?? $row->kd_pj,
            'nama_penjamin' => $admission?->nama_penjamin ?? $row->nama_penjamin,
            'kd_kamar_icu' => $icuRange->kd_kamar,
            'tgl_masuk_icu' => $this->cleanDate($icuRange->tgl_masuk),
            'jam_masuk_icu' => $this->cleanTime($icuRange->jam_masuk),
            'tgl_keluar_icu' => $this->cleanDate($icuRange->tgl_keluar),
            'jam_keluar_icu' => $this->cleanTime($icuRange->jam_keluar),
            'tanggal' => $row->tanggal,
            'jam' => $this->cleanTime($row->jam),
            'kd_tindakan' => $row->kd_tindakan,
            'nm_tindakan' => $mapping?->nm_tindakan ?? $row->nm_tindakan,
            'kd_dokter' => $row->kd_dokter,
            'nm_dokter' => $row->nm_dokter,
            'nip' => $row->nip,
            'nama_petugas' => $row->nama_petugas,
            'biaya_rawat' => (int) round((float) $row->biaya_rawat),
            'is_in_icu_range' => $isInIcuRange,
            'is_critical_action' => $isCritical,
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

    private function isCriticalAction(?string $name, Collection $criticalActionNames): bool
    {
        if ($criticalActionNames->isEmpty()) {
            return false;
        }

        $normalizedName = $this->normalizeText((string) $name);

        return $criticalActionNames
            ->contains(fn ($criticalActionName) => $normalizedName === $this->normalizeText($criticalActionName));
    }

    private function criticalActionNames(array $config): Collection
    {
        $names = collect($config['critical_action_names'] ?? []);

        if ($names->isEmpty() && ! empty($config['critical_action_name'])) {
            $names = collect([$config['critical_action_name']]);
        }

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => $this->normalizeText($name))
            ->values();
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

    private function normalizeText(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($value)) ?: '');
    }

    private function ensureDefaultConfigs(): void
    {
        foreach (['umum', 'bpjs'] as $jenis) {
            generateIcuConfigModel::query()->firstOrCreate(
                ['jenis_icu' => $jenis],
                [
                    'jnsTindakan_id' => null,
                    'perawat_icu_percent' => 30,
                    'pegawai_icu_khusus_percent' => 25,
                    'perawat_icu_reguler_percent' => 75,
                    'perawat_icu_divider' => 4,
                    'premi_medis_percent' => 25,
                    'premi_medis_divider' => 34,
                    'premi_bersama_percent' => 15,
                    'critical_action_name' => 'Perawatan ICU dan Asuhan Keperawatan KRITIKAL',
                    'critical_action_names' => ['Perawatan ICU dan Asuhan Keperawatan KRITIKAL'],
                ]
            );
        }
    }

    private function ensureConfigMappings(generateIcuConfigModel $config): void
    {
        if (! $config->jnsTindakan_id || $config->tindakan->isNotEmpty()) {
            return;
        }

        DB::table('generate_icu_config_tindakan')->insert([
            'config_id' => $config->id,
            'jnsTindakan_id' => $config->jnsTindakan_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
