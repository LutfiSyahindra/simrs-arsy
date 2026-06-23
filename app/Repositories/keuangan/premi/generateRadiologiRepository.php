<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generateRadiologiConfigModel;
use App\Models\dbSimrs\generateRadiologiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class generateRadiologiRepository
{
    public function getResults(?string $periode = null, ?string $jenisRadiologi = null): Collection
    {
        return generateRadiologiModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount('details')
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisRadiologi, fn ($query) => $query->where('jenis_radiologi', $jenisRadiologi))
            ->orderByDesc('periode')
            ->orderBy('jenis_radiologi')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisRadiologi): array
    {
        $rows = $periode
            ? generateRadiologiModel::query()
                ->where('periode', $periode)
                ->where('jenis_radiologi', $jenisRadiologi)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'total_tarif_tindakan_petugas' => $rows->sum('total_tarif_tindakan_petugas'),
            'total_biaya' => $rows->sum('total_biaya'),
            'total_manajemen' => $rows->sum('total_manajemen'),
            'total_premi_petugas' => $rows->sum('total_premi_petugas'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
        ];
    }

    public function getConfig(string $jenisRadiologi): generateRadiologiConfigModel
    {
        $this->ensureDefaultConfigs();

        return generateRadiologiConfigModel::query()
            ->with('pegawai')
            ->where('jenis_radiologi', $jenisRadiologi)
            ->firstOrFail();
    }

    public function saveConfig(
        string $jenisRadiologi,
        array $payload,
        array $recipients
    ): generateRadiologiConfigModel {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisRadiologi, $payload, $recipients) {
            $config = generateRadiologiConfigModel::query()
                ->where('jenis_radiologi', $jenisRadiologi)
                ->lockForUpdate()
                ->firstOrFail();

            if (! Schema::hasColumn('generate_radiologi_configs', 'bpjs_petugas_formula_rate')) {
                unset($payload['bpjs_petugas_formula_rate'], $payload['bpjs_petugas_formula_divider']);
            }

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
                DB::table('generate_radiologi_config_pegawai')->insert($rows);
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

    public function getSourceSummary(string $periode, string $jenisRadiologi): array
    {
        $sourcePeriode = $this->sourcePeriod($periode, $jenisRadiologi);
        [$tglAwal, $tglAkhir] = $this->periodRange($sourcePeriode);
        $managementColumn = $this->managementColumn();

        $query = DB::connection('mysql_khanza')
            ->table('periksa_radiologi as pr')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'pr.no_rawat')
            ->selectRaw('COUNT(*) as jumlah_tindakan')
            ->selectRaw('COUNT(DISTINCT pr.no_rawat) as jumlah_pasien')
            ->selectRaw('COALESCE(SUM(pr.tarif_tindakan_petugas), 0) as total_tarif_tindakan_petugas')
            ->selectRaw('COALESCE(SUM(pr.biaya), 0) as total_biaya')
            ->whereBetween('pr.tgl_periksa', [$tglAwal, $tglAkhir])
            ->when(
                $jenisRadiologi === 'bpjs',
                fn ($query) => $query->where('rp.kd_pj', 'BPJ'),
                fn ($query) => $query->whereNotIn('rp.kd_pj', ['BPJ', '-'])
            );

        $managementColumn
            ? $query->selectRaw("COALESCE(SUM(pr.{$managementColumn}), 0) as total_manajemen")
            : $query->selectRaw('0 as total_manajemen');

        $row = $query->first();

        return [
            'source_periode' => $sourcePeriode,
            'source_tgl_awal' => $tglAwal,
            'source_tgl_akhir' => $tglAkhir,
            'jumlah_tindakan' => (int) ($row->jumlah_tindakan ?? 0),
            'jumlah_pasien' => (int) ($row->jumlah_pasien ?? 0),
            'total_tarif_tindakan_petugas' => (int) round($row->total_tarif_tindakan_petugas ?? 0),
            'total_biaya' => (int) round($row->total_biaya ?? 0),
            'total_manajemen' => (int) round($row->total_manajemen ?? 0),
        ];
    }

    public function findExistingForUpdate(string $periode, string $jenisRadiologi): ?generateRadiologiModel
    {
        return generateRadiologiModel::query()
            ->where('periode', $periode)
            ->where('jenis_radiologi', $jenisRadiologi)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(string $periode, string $jenisRadiologi, array $calculation): generateRadiologiModel
    {
        $result = generateRadiologiModel::query()->updateOrCreate(
            [
                'periode' => $periode,
                'jenis_radiologi' => $jenisRadiologi,
            ],
            [
                'jumlah_tindakan' => $calculation['source']['jumlah_tindakan'],
                'jumlah_pasien' => $calculation['source']['jumlah_pasien'],
                'total_tarif_tindakan_petugas' => $calculation['source']['total_tarif_tindakan_petugas'],
                'total_biaya' => $calculation['source']['total_biaya'],
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
            'generate_radiologi_id' => $result->id,
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
            DB::table('generate_radiologi_detail')->insert($details);
        }

        return $result->fresh(['details', 'lockedBy:id,name', 'generateBy:id,name']);
    }

    public function findForUpdate(int $id): ?generateRadiologiModel
    {
        return generateRadiologiModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generateRadiologiModel
    {
        return generateRadiologiModel::query()
            ->with([
                'details' => fn ($query) => $query->orderBy('pegawai_name'),
                'lockedBy:id,name',
                'generateBy:id,name',
            ])
            ->find($id);
    }

    public function updateLock(
        generateRadiologiModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generateRadiologiModel {
        DB::table('generate_radiologi')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generateRadiologiModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generateRadiologiModel $result): void
    {
        $result->delete();
    }

    private function ensureDefaultConfigs(): void
    {
        $formulaPayload = $this->bpjsFormulaPayload();

        generateRadiologiConfigModel::query()->firstOrCreate(
            ['jenis_radiologi' => 'umum'],
            [
                'petugas_mode' => 'percent',
                'petugas_percent' => 25,
                'petugas_nominal' => 0,
                ...$formulaPayload,
                'bersama_mode' => 'source',
                'bersama_percent' => 100,
                'bersama_nominal' => 0,
            ]
        );

        $bpjsConfig = generateRadiologiConfigModel::query()->firstOrCreate(
            ['jenis_radiologi' => 'bpjs'],
            [
                'petugas_mode' => 'percent',
                'petugas_percent' => $this->bpjsPetugasPercent(),
                'petugas_nominal' => 0,
                ...$formulaPayload,
                'bersama_mode' => 'petugas',
                'bersama_percent' => 100,
                'bersama_nominal' => 0,
            ]
        );

        if (
            ! $bpjsConfig->wasRecentlyCreated
            && $bpjsConfig->bersama_mode === 'source'
            && (float) $bpjsConfig->bersama_percent === 100.0
            && (int) $bpjsConfig->bersama_nominal === 0
        ) {
            $bpjsConfig->update(['bersama_mode' => 'petugas']);
        }
    }

    private function bpjsPetugasPercent(): float
    {
        return (0.04 / 4) * 100;
    }

    private function bpjsFormulaPayload(): array
    {
        if (! Schema::hasColumn('generate_radiologi_configs', 'bpjs_petugas_formula_rate')) {
            return [];
        }

        return [
            'bpjs_petugas_formula_rate' => 0.04,
            'bpjs_petugas_formula_divider' => 4,
        ];
    }

    private function periodRange(string $periode): array
    {
        $start = $periode.'-01';
        $end = date('Y-m-t', strtotime($start));

        return [$start, $end];
    }

    private function sourcePeriod(string $periode, string $jenisRadiologi): string
    {
        if ($jenisRadiologi !== 'bpjs') {
            return $periode;
        }

        return date('Y-m', strtotime($periode.'-01 -1 month'));
    }

    private function managementColumn(): ?string
    {
        foreach (['manajemen', 'menejemen'] as $column) {
            if (Schema::connection('mysql_khanza')->hasColumn('periksa_radiologi', $column)) {
                return $column;
            }
        }

        return null;
    }
}
