<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateGiziConfigModel;
use App\Models\dbSimrs\generateGiziModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generateGiziRepository
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

    public function getResults(?string $periode = null, ?string $jenisGizi = null): Collection
    {
        return generateGiziModel::query()
            ->with(['jenisTindakan:id,kode,jenis', 'lockedBy:id,name', 'generateBy:id,name'])
            ->withCount(['details', 'recipients'])
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisGizi, fn ($query) => $query->where('jenis_gizi', $jenisGizi))
            ->orderByDesc('periode')
            ->orderBy('jenis_gizi')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisGizi): array
    {
        $rows = $periode
            ? generateGiziModel::query()
                ->where('periode', $periode)
                ->where('jenis_gizi', $jenisGizi)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_data_sumber' => $rows->sum('jumlah_data_sumber'),
            'jumlah_pasien_sumber' => $rows->sum('jumlah_pasien_sumber'),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'jumlah_tindakan_konsul' => $rows->sum('jumlah_tindakan_konsul'),
            'jumlah_tindakan_diit' => $rows->sum('jumlah_tindakan_diit'),
            'grand_total' => $rows->sum('grand_total'),
            'grand_total_konsul' => $rows->sum('grand_total_konsul'),
            'grand_total_diit' => $rows->sum('grand_total_diit'),
            'total_konsul_pegawai' => $rows->sum('total_konsul_pegawai'),
            'total_konsul_premi_bersama' => $rows->sum('total_konsul_premi_bersama'),
            'total_diit_petugas_pool' => $rows->sum('total_diit_petugas_pool'),
            'diit_petugas_per_orang' => $rows->sum('diit_petugas_per_orang'),
            'total_diit_premi_bersama' => $rows->sum('total_diit_premi_bersama'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
        ];
    }

    public function getConfig(string $jenisGizi): generateGiziConfigModel
    {
        $this->ensureDefaultConfigs();

        $config = generateGiziConfigModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'mappings.jenisTindakan:id,kode,jenis',
                'pegawai',
            ])
            ->where('jenis_gizi', $jenisGizi)
            ->firstOrFail();

        $this->ensureConfigMappings($config);

        return $config->fresh([
            'jenisTindakan:id,kode,jenis',
            'mappings.jenisTindakan:id,kode,jenis',
            'pegawai',
        ]);
    }

    public function saveConfig(
        string $jenisGizi,
        array $payload,
        array $mappingGroups,
        array $recipients
    ): generateGiziConfigModel {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisGizi, $payload, $mappingGroups, $recipients) {
            $config = generateGiziConfigModel::query()
                ->where('jenis_gizi', $jenisGizi)
                ->lockForUpdate()
                ->firstOrFail();

            $mappingGroups = $this->normalizeMappingGroups($mappingGroups);
            $payload['jnsTindakan_id'] = collect($mappingGroups)
                ->flatten()
                ->first();

            $config->update($payload);
            $config->mappings()->delete();
            $config->pegawai()->delete();

            $now = now();
            $mappingRows = [];
            foreach ($mappingGroups as $kelompok => $ids) {
                foreach ($ids as $id) {
                    $mappingRows[] = [
                        'config_id' => $config->id,
                        'kelompok' => $kelompok,
                        'jnsTindakan_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($mappingRows)) {
                DB::table('generate_gizi_config_mapping')->insert($mappingRows);
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
                DB::table('generate_gizi_config_pegawai')->insert($pegawaiRows);
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

    public function getSourceData(string $periode, string $jenisGizi, array $config): array
    {
        $sourcePeriodMode = $this->normalizeSourcePeriodMode($config['source_period_mode'] ?? 'current');
        $range = $this->sourceRange($periode, $sourcePeriodMode);
        $sourceStart = $range['start'];
        $sourceEnd = $range['end'];
        $sourceEndInclusive = $sourceEnd->copy()->subDay();
        $mappingGroups = [
            'konsul' => $config['konsul_jnsTindakan_ids'] ?? [],
            'diit' => $config['diit_jnsTindakan_ids'] ?? [],
        ];
        $mappings = $this->selectedMappings($mappingGroups);
        $sourceSummary = $this->sourceSummary($jenisGizi, $sourceStart, $sourceEnd);
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
                $rows = $this->sourceQuery(
                    $definition,
                    $jenisGizi,
                    $sourceStart,
                    $sourceEnd,
                    $codeChunk->values()
                )->get();

                foreach ($rows as $row) {
                    foreach ($mappingByCode->get($row->kd_tindakan, collect()) as $mapping) {
                        $details->push($this->detailPayload($row, $mapping));
                    }
                }
            }
        }

        $details = $details
            ->unique(fn ($row) => implode('|', [
                $row['kelompok'],
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
                $row['kelompok'],
                $row['tanggal'],
                $row['jam'] ?? '',
                $row['no_rawat'],
                $row['source_table'],
                $row['kd_tindakan'],
            ]))
            ->values();

        return [
            'source_periode' => $sourceStart->format('Y-m'),
            'source_period_mode' => $sourcePeriodMode,
            'source_period_mode_label' => $this->sourcePeriodModeLabel($sourcePeriodMode),
            'source_tgl_awal' => $sourceStart->toDateString(),
            'source_tgl_akhir' => $sourceEndInclusive->toDateString(),
            'jumlah_data_sumber' => $sourceSummary['jumlah_data_sumber'],
            'jumlah_pasien_sumber' => $sourceSummary['jumlah_pasien_sumber'],
            'jumlah_pasien' => $details->pluck('no_rawat')->unique()->count(),
            'jumlah_tindakan' => $details->count(),
            'jumlah_tindakan_konsul' => $details->where('kelompok', 'konsul')->count(),
            'jumlah_tindakan_diit' => $details->where('kelompok', 'diit')->count(),
            'grand_total' => (int) round($details->sum('biaya_rawat')),
            'grand_total_konsul' => (int) round($details->where('kelompok', 'konsul')->sum('biaya_rawat')),
            'grand_total_diit' => (int) round($details->where('kelompok', 'diit')->sum('biaya_rawat')),
            'mapping' => $this->mappingSummary($mappingGroups, $mappings),
            'details' => $details,
        ];
    }

    public function findExistingForUpdate(string $periode, string $jenisGizi): ?generateGiziModel
    {
        return generateGiziModel::query()
            ->where('periode', $periode)
            ->where('jenis_gizi', $jenisGizi)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisGizi, array $calculation): generateGiziModel
    {
        $source = $calculation['source'];
        $mapping = $source['mapping'];
        $primaryMapping = $mapping['primary'] ?? null;
        $pools = $calculation['pools'];

        $result = generateGiziModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_gizi' => $jenisGizi,
            ],
            [
                'source_periode' => $source['source_periode'],
                'source_period_mode' => $source['source_period_mode'],
                'source_tgl_awal' => $source['source_tgl_awal'],
                'source_tgl_akhir' => $source['source_tgl_akhir'],
                'jnsTindakan_id' => $primaryMapping['id'] ?? null,
                'kode_jenis_tindakan' => $primaryMapping['kode'] ?? null,
                'nama_jenis_tindakan' => $mapping['label'] ?? ($primaryMapping['jenis'] ?? null),
                'jumlah_data_sumber' => $source['jumlah_data_sumber'],
                'jumlah_pasien_sumber' => $source['jumlah_pasien_sumber'],
                'jumlah_pasien' => $source['jumlah_pasien'],
                'jumlah_tindakan' => $source['jumlah_tindakan'],
                'jumlah_tindakan_konsul' => $source['jumlah_tindakan_konsul'],
                'jumlah_tindakan_diit' => $source['jumlah_tindakan_diit'],
                'grand_total' => $source['grand_total'],
                'grand_total_konsul' => $source['grand_total_konsul'],
                'grand_total_diit' => $source['grand_total_diit'],
                'total_konsul_pegawai' => $pools['konsul_pegawai'],
                'total_konsul_premi_bersama' => $pools['konsul_premi_bersama'],
                'total_diit_petugas_pool' => $pools['diit_petugas_pool'],
                'diit_petugas_per_orang' => $pools['diit_petugas_per_orang'],
                'total_diit_premi_bersama' => $pools['diit_premi_bersama'],
                'total_premi_bersama' => $pools['premi_bersama'],
                'total_dibagikan' => $pools['total_dibagikan'],
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
                'generate_gizi_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(500)
            ->each(fn (Collection $chunk) => DB::table('generate_gizi_detail')->insert($chunk->all()));

        collect($calculation['recipients'])
            ->map(fn (array $recipient) => [
                ...$recipient,
                'generate_gizi_id' => $result->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(200)
            ->each(fn (Collection $chunk) => DB::table('generate_gizi_recipient')->insert($chunk->all()));

        return $result->fresh([
            'details',
            'recipients',
            'jenisTindakan:id,kode,jenis',
            'lockedBy:id,name',
            'generateBy:id,name',
        ]);
    }

    public function findForUpdate(int $id): ?generateGiziModel
    {
        return generateGiziModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateGiziModel
    {
        return generateGiziModel::query()
            ->with([
                'jenisTindakan:id,kode,jenis',
                'lockedBy:id,name',
                'generateBy:id,name',
                'details' => fn ($query) => $query
                    ->orderByRaw("FIELD(kelompok, 'konsul', 'diit')")
                    ->orderBy('tanggal')
                    ->orderBy('jam')
                    ->orderBy('no_rawat')
                    ->orderBy('source_table'),
                'recipients' => fn ($query) => $query
                    ->orderByRaw("FIELD(role, 'konsul_pegawai', 'diit_petugas')")
                    ->orderBy('pegawai_name'),
            ])
            ->find($id);
    }

    public function updateLock(
        generateGiziModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateGiziModel {
        DB::table('generate_gizi')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateGiziModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateGiziModel $result): void
    {
        $result->delete();
    }

    private function sourceQuery(
        array $definition,
        string $jenisGizi,
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
                $jenisGizi === 'bpjs',
                fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                fn ($query) => $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
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

    private function sourceSummary(string $jenisGizi, Carbon $start, Carbon $end): array
    {
        $jumlahData = 0;
        $patients = collect();

        foreach (self::SOURCE_TABLES as $definition) {
            $query = DB::connection('mysql_khanza')
                ->table($definition['table'].' as r')
                ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'r.no_rawat')
                ->where('r.tgl_perawatan', '>=', $start->toDateString())
                ->where('r.tgl_perawatan', '<', $end->toDateString())
                ->where('r.biaya_rawat', '>', 0)
                ->when(
                    $jenisGizi === 'bpjs',
                    fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                    fn ($query) => $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
                );

            $jumlahData += (clone $query)->count();
            $patients = $patients->merge(
                (clone $query)
                    ->distinct()
                    ->pluck('r.no_rawat')
            );
        }

        return [
            'jumlah_data_sumber' => $jumlahData,
            'jumlah_pasien_sumber' => $patients->unique()->count(),
        ];
    }

    private function selectedMappings(array $mappingGroups): Collection
    {
        $rows = collect();

        foreach ($this->normalizeMappingGroups($mappingGroups) as $kelompok => $ids) {
            if (empty($ids)) {
                continue;
            }

            $rows = $rows->merge(
                DB::table('mapping_tindakan as mt')
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
                    ->whereIn('mt.jnsTindakan_id', $ids)
                    ->get()
                    ->map(function ($row) use ($kelompok) {
                        $row->kelompok = $kelompok;

                        return $row;
                    })
            );
        }

        return $rows
            ->unique(fn ($row) => $row->kelompok.'|'.$row->sumber_tindakan.'|'.$row->kd_tindakan)
            ->values();
    }

    private function detailPayload(object $row, object $mapping): array
    {
        return [
            'mapping_tindakan_id' => $mapping->id,
            'jnsTindakan_id' => $mapping->jnsTindakan_id,
            'kelompok' => $mapping->kelompok,
            'source_table' => $row->source_table,
            'sumber_tindakan' => $row->sumber_tindakan,
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
            'biaya_rawat' => (int) round((float) $row->biaya_rawat),
        ];
    }

    private function mappingSummary(array $mappingGroups, Collection $mappings): array
    {
        $mappingGroups = $this->normalizeMappingGroups($mappingGroups);
        $allIds = collect($mappingGroups)->flatten()->unique()->values();

        if ($allIds->isEmpty()) {
            return [
                'label' => null,
                'primary' => null,
                'jumlah_mapping' => 0,
                'jumlah_jenis_tindakan' => 0,
                'groups' => [
                    'konsul' => $this->emptyMappingGroup('konsul'),
                    'diit' => $this->emptyMappingGroup('diit'),
                ],
            ];
        }

        $jenisRows = DB::table('master_jenis_tindakan')
            ->select('id', 'kode', 'jenis')
            ->whereIn('id', $allIds->all())
            ->orderBy('jenis')
            ->get()
            ->keyBy('id');

        $groups = [];
        foreach (['konsul', 'diit'] as $kelompok) {
            $ids = collect($mappingGroups[$kelompok] ?? []);
            $items = $ids
                ->map(fn ($id) => $jenisRows->get($id))
                ->filter()
                ->map(fn ($jenis) => [
                    'id' => (int) $jenis->id,
                    'kode' => $jenis->kode,
                    'jenis' => $jenis->jenis,
                    'label' => trim($jenis->kode.' - '.$jenis->jenis),
                    'jumlah_mapping' => $mappings
                        ->where('kelompok', $kelompok)
                        ->where('jnsTindakan_id', (int) $jenis->id)
                        ->count(),
                ])
                ->values();

            $first = $items->first();
            $label = $items->count() > 1
                ? $first['label'].' + '.($items->count() - 1).' mapping'
                : ($first['label'] ?? null);

            $groups[$kelompok] = [
                'kelompok' => $kelompok,
                'kelompok_label' => $this->groupLabel($kelompok),
                'ids' => $items->pluck('id')->all(),
                'items' => $items->all(),
                'label' => $label,
                'jumlah_mapping' => $mappings->where('kelompok', $kelompok)->count(),
                'jumlah_jenis_tindakan' => $items->count(),
            ];
        }

        $primary = collect($groups['konsul']['items'])
            ->merge($groups['diit']['items'])
            ->first();
        $selectedCount = count($groups['konsul']['items']) + count($groups['diit']['items']);
        $label = $primary
            ? ($selectedCount > 1 ? $primary['label'].' + '.($selectedCount - 1).' mapping' : $primary['label'])
            : null;

        return [
            'label' => $label,
            'primary' => $primary,
            'jumlah_mapping' => $mappings->count(),
            'jumlah_jenis_tindakan' => $selectedCount,
            'groups' => $groups,
        ];
    }

    private function emptyMappingGroup(string $kelompok): array
    {
        return [
            'kelompok' => $kelompok,
            'kelompok_label' => $this->groupLabel($kelompok),
            'ids' => [],
            'items' => [],
            'label' => null,
            'jumlah_mapping' => 0,
            'jumlah_jenis_tindakan' => 0,
        ];
    }

    private function normalizeMappingGroups(array $mappingGroups): array
    {
        $normalized = [];

        foreach (['konsul', 'diit'] as $kelompok) {
            $normalized[$kelompok] = collect($mappingGroups[$kelompok] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $normalized;
    }

    private function ensureDefaultConfigs(): void
    {
        $defaults = [
            'umum' => [
                'source_period_mode' => 'current',
                'konsul_pegawai_percent' => 50,
                'konsul_premi_bersama_percent' => 30,
                'diit_petugas_percent' => 3,
                'diit_petugas_divider' => 5,
                'diit_premi_bersama_percent' => 37,
                'diit_premi_bersama_enabled' => true,
            ],
            'bpjs' => [
                'source_period_mode' => 'current',
                'konsul_pegawai_percent' => 50,
                'konsul_premi_bersama_percent' => 37,
                'diit_petugas_percent' => 1,
                'diit_petugas_divider' => 6,
                'diit_premi_bersama_percent' => 0,
                'diit_premi_bersama_enabled' => false,
            ],
        ];

        foreach ($defaults as $jenis => $payload) {
            generateGiziConfigModel::query()->firstOrCreate(
                ['jenis_gizi' => $jenis],
                [
                    'jnsTindakan_id' => null,
                    ...$payload,
                ]
            );
        }
    }

    private function ensureConfigMappings(generateGiziConfigModel $config): void
    {
        if (! $config->jnsTindakan_id || $config->mappings->isNotEmpty()) {
            return;
        }

        DB::table('generate_gizi_config_mapping')->insert([
            'config_id' => $config->id,
            'kelompok' => 'konsul',
            'jnsTindakan_id' => $config->jnsTindakan_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sourceRange(string $periode, string $mode): array
    {
        $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();

        if ($mode === 'previous') {
            $start->subMonth();
        }

        return [
            'start' => $start,
            'end' => $start->copy()->addMonth(),
        ];
    }

    private function normalizeSourcePeriodMode(?string $mode): string
    {
        return $mode === 'previous' ? 'previous' : 'current';
    }

    private function sourcePeriodModeLabel(string $mode): string
    {
        return $mode === 'previous' ? 'Bulan Sebelumnya' : 'Periode Generate';
    }

    private function cleanTime(?string $time): ?string
    {
        $time = trim((string) $time);

        return $time === '' ? null : $time;
    }

    private function groupLabel(string $kelompok): string
    {
        return $kelompok === 'diit' ? 'Diit' : 'Konsul';
    }
}
