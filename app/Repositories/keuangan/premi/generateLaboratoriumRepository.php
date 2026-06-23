<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateLaboratoriumConfigModel;
use App\Models\dbSimrs\generateLaboratoriumModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class generateLaboratoriumRepository
{
    public function getResults(?string $periode = null, ?string $jenisLaboratorium = null): Collection
    {
        return generateLaboratoriumModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisLaboratorium, fn ($query) => $query->where('jenis_laboratorium', $jenisLaboratorium))
            ->orderByDesc('periode')
            ->orderBy('jenis_laboratorium')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisLaboratorium): array
    {
        $rows = $periode
            ? generateLaboratoriumModel::query()
                ->where('periode', $periode)
                ->where('jenis_laboratorium', $jenisLaboratorium)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'total_bagian_laborat' => $rows->sum('total_bagian_laborat'),
            'total_bagian_rs' => $rows->sum('total_bagian_rs'),
            'total_manajemen' => $rows->sum('total_manajemen'),
            'total_premi_petugas' => $rows->sum('total_premi_petugas'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
        ];
    }

    public function getConfig(string $jenisLaboratorium): generateLaboratoriumConfigModel
    {
        $this->ensureDefaultConfigs();

        return generateLaboratoriumConfigModel::query()
            ->with('pegawai')
            ->where('jenis_laboratorium', $jenisLaboratorium)
            ->firstOrFail();
    }

    public function saveConfig(
        string $jenisLaboratorium,
        array $payload,
        array $recipients
    ): generateLaboratoriumConfigModel {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisLaboratorium, $payload, $recipients) {
            $config = generateLaboratoriumConfigModel::query()
                ->where('jenis_laboratorium', $jenisLaboratorium)
                ->lockForUpdate()
                ->firstOrFail();

            $config->update($payload);
            $config->pegawai()->delete();

            $now = now();
            $rows = collect($recipients)->map(fn ($item) => [
                'config_id' => $config->id,
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows) {
                DB::table('generate_laboratorium_config_pegawai')->insert($rows);
            }

            return $config->fresh('pegawai');
        });
    }

    public function searchPegawai(?string $keyword = null): Collection
    {
        return gapokModel::query()
            ->select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('stts_aktif', 'AKTIF')
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
            ->first();
    }

    public function getSourceSummary(string $periode, string $jenisLaboratorium): array
    {
        $sourcePeriode = $this->sourcePeriod($periode, $jenisLaboratorium);
        [$tglAwal, $tglAkhir] = $this->periodRange($sourcePeriode);
        $managementColumn = $this->detailManagementColumn();

        $query = DB::connection('mysql_khanza')
            ->table('detail_periksa_lab as dpl')
            ->join('periksa_lab as pl', function ($join) {
                $join->on('pl.no_rawat', '=', 'dpl.no_rawat')
                    ->on('pl.kd_jenis_prw', '=', 'dpl.kd_jenis_prw')
                    ->on('pl.tgl_periksa', '=', 'dpl.tgl_periksa')
                    ->on('pl.jam', '=', 'dpl.jam');
            })
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'dpl.no_rawat')
            ->selectRaw('COUNT(*) as jumlah_tindakan')
            ->selectRaw('COUNT(DISTINCT dpl.no_rawat) as jumlah_pasien')
            ->selectRaw('COALESCE(SUM(dpl.bagian_laborat), 0) as total_bagian_laborat')
            ->selectRaw('COALESCE(SUM(dpl.bagian_rs), 0) as total_bagian_rs')
            ->whereBetween('dpl.tgl_periksa', [$tglAwal, $tglAkhir])
            ->when(
                $jenisLaboratorium === 'bpjs',
                fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                fn ($query) => $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
            );

        $managementColumn
            ? $query->selectRaw("COALESCE(SUM(dpl.{$managementColumn}), 0) as total_manajemen")
            : $query->selectRaw('0 as total_manajemen');

        $row = $query->first();

        return [
            'source_periode' => $sourcePeriode,
            'source_tgl_awal' => $tglAwal,
            'source_tgl_akhir' => $tglAkhir,
            'jumlah_tindakan' => (int) ($row->jumlah_tindakan ?? 0),
            'jumlah_pasien' => (int) ($row->jumlah_pasien ?? 0),
            'total_bagian_laborat' => (int) round($row->total_bagian_laborat ?? 0),
            'total_bagian_rs' => (int) round($row->total_bagian_rs ?? 0),
            'total_manajemen' => (int) round($row->total_manajemen ?? 0),
        ];
    }

    public function findExistingForUpdate(string $periode, string $jenisLaboratorium): ?generateLaboratoriumModel
    {
        return generateLaboratoriumModel::query()
            ->where('periode', $periode)
            ->where('jenis_laboratorium', $jenisLaboratorium)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisLaboratorium, array $calculation): generateLaboratoriumModel
    {
        $result = generateLaboratoriumModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_laboratorium' => $jenisLaboratorium,
            ],
            [
                'jumlah_tindakan' => $calculation['source']['jumlah_tindakan'],
                'jumlah_pasien' => $calculation['source']['jumlah_pasien'],
                'total_bagian_laborat' => $calculation['source']['total_bagian_laborat'],
                'total_bagian_rs' => $calculation['source']['total_bagian_rs'],
                'total_manajemen' => $calculation['source']['total_manajemen'],
                'total_premi_petugas' => $calculation['pools']['petugas'],
                'total_premi_bersama' => $calculation['pools']['bersama'],
                'total_dibagikan' => $calculation['total_dibagikan'],
                'config_snapshot' => $calculation['config_snapshot'] ?? $calculation['config'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();

        $now = now();
        $details = collect($calculation['recipients'])->map(fn ($item) => [
            'generate_laboratorium_id' => $result->id,
            'role' => $item['role'],
            'role_label' => $item['role_label'],
            'pegawai_id' => $item['pegawai_id'],
            'pegawai_name' => $item['pegawai_name'],
            'pegawai_position' => $item['pegawai_position'],
            'allocation_percent' => $item['allocation_percent'],
            'pool_total' => $item['pool_total'],
            'total_received' => $item['total_received'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($details) {
            DB::table('generate_laboratorium_detail')->insert($details);
        }

        return $result->fresh(['details', 'lockedBy:id,name', 'generateBy:id,name']);
    }

    public function findForUpdate(int $id): ?generateLaboratoriumModel
    {
        return generateLaboratoriumModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateLaboratoriumModel
    {
        return generateLaboratoriumModel::query()
            ->with([
                'details' => fn ($query) => $query->orderBy('pegawai_name'),
                'lockedBy:id,name',
                'generateBy:id,name',
            ])
            ->find($id);
    }

    public function updateLock(
        generateLaboratoriumModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateLaboratoriumModel {
        DB::table('generate_laboratorium')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateLaboratoriumModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateLaboratoriumModel $result): void
    {
        $result->delete();
    }

    private function ensureDefaultConfigs(): void
    {
        generateLaboratoriumConfigModel::query()->firstOrCreate(
            ['jenis_laboratorium' => 'umum'],
            [
                'petugas_mode' => 'percent',
                'petugas_percent' => 25,
                'petugas_nominal' => 0,
                'bpjs_petugas_divider' => 7,
                'bersama_mode' => 'source',
                'bersama_percent' => 100,
                'bersama_nominal' => 0,
            ]
        );

        generateLaboratoriumConfigModel::query()->firstOrCreate(
            ['jenis_laboratorium' => 'bpjs'],
            [
                'petugas_mode' => 'bersama_divider',
                'petugas_percent' => 0,
                'petugas_nominal' => 0,
                'bpjs_petugas_divider' => 7,
                'bersama_mode' => 'percent',
                'bersama_percent' => 4,
                'bersama_nominal' => 0,
            ]
        );

    }

    private function periodRange(string $periode): array
    {
        $start = $periode.'-01';
        $end = date('Y-m-t', strtotime($start));

        return [$start, $end];
    }

    private function sourcePeriod(string $periode, string $jenisLaboratorium): string
    {
        if ($jenisLaboratorium !== 'bpjs') {
            return $periode;
        }

        return date('Y-m', strtotime($periode.'-01 -1 month'));
    }

    private function detailManagementColumn(): ?string
    {
        foreach (['manajemen', 'menejemen'] as $column) {
            if (Schema::connection('mysql_khanza')->hasColumn('detail_periksa_lab', $column)) {
                return $column;
            }
        }

        return null;
    }
}
