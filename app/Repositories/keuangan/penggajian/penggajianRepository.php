<?php

namespace App\Repositories\keuangan\penggajian;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gajiTahap1Model;
use App\Models\dbSimrs\gajiTahap2DetailModel;
use App\Models\dbSimrs\gajiTahap2Model;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\potonganPegawaiModel;
use App\Models\dbSimrs\tunjanganPegawaiModel;
use App\Support\PremiSourcePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class penggajianRepository
{
    public function getUnitKerjaByNik(string $nik): ?string
    {
        $nik = trim($nik);

        if ($nik === '' || ! Schema::hasTable('unit_pegawai') || ! Schema::hasTable('master_unit')) {
            return null;
        }

        $query = DB::table('unit_pegawai as up')
            ->join('master_unit as mu', 'mu.id', '=', 'up.unit_id')
            ->where('up.nik', $nik);

        if (Schema::hasColumn('unit_pegawai', 'deleted_at')) {
            $query->whereNull('up.deleted_at');
        }

        $units = $query
            ->select([
                'mu.kode',
                'mu.keterangan',
            ])
            ->orderBy('mu.jenis')
            ->orderBy('mu.keterangan')
            ->get();

        $label = $units
            ->map(function ($unit) {
                $kode = trim((string) ($unit->kode ?? ''));
                $keterangan = trim((string) ($unit->keterangan ?? ''));

                return $keterangan !== '' ? $keterangan : $kode;
            })
            ->filter()
            ->unique()
            ->implode(', ');

        return $label !== '' ? $label : null;
    }

    public function getTunjanganPegawai($nik)
    {
        return tunjanganPegawaiModel::query()
            ->leftJoin('master_tunjangan', 'master_tunjangan.id', '=', 'tunjangan_pegawai.tunjangan_id')
            ->where('tunjangan_pegawai.nik', $nik)
            ->select([
                'tunjangan_pegawai.nominal',
                'master_tunjangan.nama as nama_tunjangan',
            ])
            ->get();
    }

    public function getGajiTahap1Table($periode)
    {
        $select = [
            'id',
            'nik',
            'nama',
            'jabatan',
            'status',
            'gaji_pokok',
            'gaji_dibayar',
            'tunjangan',
            Schema::hasColumn('gaji_tahap1', 'premi')
                ? 'premi'
                : DB::raw('0 as premi'),
            'periode',
            Schema::hasColumn('gaji_tahap1', 'pembulatan')
                ? 'pembulatan'
                : DB::raw('0 as pembulatan'),
            Schema::hasColumn('gaji_tahap1', 'total')
                ? 'total'
                : DB::raw('COALESCE(gaji_dibayar, 0) + COALESCE(tunjangan, 0) as total'),
        ];

        return gajiTahap1Model::select($select)
            ->where('periode', $periode)
            ->get();
    }

    public function getPenerimaSlipWhatsappTahap1($periode, array $ids = [])
    {
        return gajiTahap1Model::query()
            ->join('gaji_pokok', 'gaji_pokok.nik', '=', 'gaji_tahap1.nik')
            ->select([
                'gaji_tahap1.id',
                'gaji_tahap1.nik',
                'gaji_tahap1.nama',
                'gaji_tahap1.jabatan',
                'gaji_tahap1.status',
                'gaji_tahap1.gaji_pokok',
                'gaji_tahap1.gaji_dibayar',
                'gaji_tahap1.tunjangan',
                Schema::hasColumn('gaji_tahap1', 'premi')
                    ? 'gaji_tahap1.premi'
                    : DB::raw('0 as premi'),
                Schema::hasColumn('gaji_tahap1', 'pembulatan')
                    ? 'gaji_tahap1.pembulatan'
                    : DB::raw('0 as pembulatan'),
                Schema::hasColumn('gaji_tahap1', 'total')
                    ? 'gaji_tahap1.total'
                    : DB::raw('COALESCE(gaji_tahap1.gaji_dibayar, 0) + COALESCE(gaji_tahap1.tunjangan, 0) as total'),
                'gaji_tahap1.periode',
                'gaji_pokok.no_telp',
            ])
            ->where('gaji_tahap1.periode', $periode)
            ->whereNotNull('gaji_pokok.no_telp')
            ->whereRaw("TRIM(gaji_pokok.no_telp) <> ''")
            ->when(! empty($ids), function ($query) use ($ids) {
                $query->whereIn('gaji_tahap1.id', $ids);
            })
            ->orderBy('gaji_tahap1.nama')
            ->get();
    }

    public function getPenerimaSlipWhatsappTahap2($periode, array $ids = []): Collection
    {
        if (! Schema::hasTable('gaji_tahap2')) {
            return collect();
        }

        return gajiTahap2Model::query()
            ->join('gaji_pokok', 'gaji_pokok.nik', '=', 'gaji_tahap2.nik')
            ->select([
                'gaji_tahap2.id',
                'gaji_tahap2.nik',
                'gaji_tahap2.nama',
                'gaji_tahap2.jabatan',
                'gaji_tahap2.status',
                'gaji_tahap2.gaji_pokok',
                'gaji_tahap2.gaji_dibayar',
                'gaji_tahap2.total_premi',
                Schema::hasColumn('gaji_tahap2', 'total_potongan')
                    ? 'gaji_tahap2.total_potongan'
                    : DB::raw('0 as total_potongan'),
                Schema::hasColumn('gaji_tahap2', 'pembulatan')
                    ? 'gaji_tahap2.pembulatan'
                    : DB::raw('0 as pembulatan'),
                'gaji_tahap2.total',
                'gaji_tahap2.periode',
                'gaji_pokok.no_telp',
            ])
            ->where('gaji_tahap2.periode', $periode)
            ->whereNotNull('gaji_pokok.no_telp')
            ->whereRaw("TRIM(gaji_pokok.no_telp) <> ''")
            ->when(! empty($ids), function ($query) use ($ids) {
                $query->whereIn('gaji_tahap2.id', $ids);
            })
            ->orderBy('gaji_tahap2.nama')
            ->get();
    }

    public function getPegawaiUntukGajiTahap1()
    {
        $pegawaiList = pegawaiModel::query()
            ->select([
                'nik',
                'nama',
                'jbtn',
                'stts_kerja',
            ])
            ->where('stts_aktif', 'AKTIF')
            ->get();

        $gajiPokokList = gapokModel::query()
            ->pluck('gaji_pokok', 'nik');

        $tunjanganList = tunjanganPegawaiModel::query()
            ->selectRaw('nik, SUM(nominal) as total_tunjangan')
            ->groupBy('nik')
            ->pluck('total_tunjangan', 'nik');

        return $pegawaiList->map(function ($pegawai) use ($gajiPokokList, $tunjanganList) {
            $pegawai->status = $pegawai->stts_kerja;
            $pegawai->nominal_gaji_pokok = $gajiPokokList[$pegawai->nik] ?? 0;
            $pegawai->nominal_tunjangan = $tunjanganList[$pegawai->nik] ?? 0;

            return $pegawai;
        });
    }

    public function getPegawaiUntukGajiTahap2(): Collection
    {
        $pegawaiList = pegawaiModel::query()
            ->select([
                'nik',
                'nama',
                'jbtn',
                'stts_kerja',
            ])
            ->where('stts_aktif', 'AKTIF')
            ->whereNotNull('stts_kerja')
            ->whereRaw("TRIM(stts_kerja) <> ''")
            ->whereRaw("UPPER(TRIM(stts_kerja)) NOT IN ('MT', 'MITRA', '-')")
            ->get();

        $gajiPokokList = gapokModel::query()
            ->pluck('gaji_pokok', 'nik');

        return $pegawaiList->map(function ($pegawai) use ($gajiPokokList) {
            $pegawai->status = $pegawai->stts_kerja;
            $pegawai->nominal_gaji_pokok = $gajiPokokList[$pegawai->nik] ?? 0;

            return $pegawai;
        });
    }

    public function dokterUmumOptions(?string $keyword = null): Collection
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
            ->where(function ($query) {
                $query
                    ->whereNull('s.nm_sps')
                    ->orWhere('s.nm_sps', '')
                    ->orWhereRaw('LOWER(s.nm_sps) LIKE ?', ['%umum%']);
            })
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

    public function dokterUgdKontrakOptions(?string $keyword = null): Collection
    {
        return DB::connection('mysql_khanza')
            ->table('pegawai as p')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'p.nik')
            ->leftJoin('spesialis as s', 's.kd_sps', '=', 'd.kd_sps')
            ->select([
                'p.nik as kd_dokter',
                DB::raw('COALESCE(d.nm_dokter, p.nama) as nm_dokter'),
                'd.kd_sps',
                's.nm_sps',
            ])
            ->where('p.stts_aktif', 'AKTIF')
            ->whereRaw('LOWER(TRIM(p.jbtn)) = ?', ['dokter unit gawat darurat'])
            ->whereRaw("UPPER(TRIM(p.stts_kerja)) IN ('FT', 'KONTRAK', 'PEGAWAI KONTRAK', 'FT>1')")
            ->when($keyword, function ($query) use ($keyword) {
                $keyword = '%'.$keyword.'%';
                $query->where(function ($where) use ($keyword) {
                    $where
                        ->where('p.nik', 'like', $keyword)
                        ->orWhere('p.nama', 'like', $keyword)
                        ->orWhere('d.kd_dokter', 'like', $keyword)
                        ->orWhere('d.nm_dokter', 'like', $keyword)
                        ->orWhere('s.nm_sps', 'like', $keyword);
                });
            })
            ->orderByRaw('COALESCE(d.nm_dokter, p.nama)')
            ->limit(50)
            ->get();
    }

    public function getDoctorNikLookup(array $niks): Collection
    {
        $nikList = collect($niks)
            ->filter(fn ($nik) => filled($nik))
            ->map(fn ($nik) => trim((string) $nik))
            ->filter(fn (string $nik) => $nik !== '')
            ->unique()
            ->values();

        if ($nikList->isEmpty()) {
            return collect();
        }

        return DB::connection('mysql_khanza')
            ->table('dokter')
            ->whereIn('kd_dokter', $nikList->all())
            ->pluck('kd_dokter')
            ->mapWithKeys(fn ($nik) => ['nik:'.trim((string) $nik) => true]);
    }

    public function getGajiTahap1DoctorConfigs(bool $activeOnly = true): Collection
    {
        return $this->getGajiDoctorConfigs('gaji_tahap1_dokter_config', $activeOnly);
    }

    public function getGajiTahap2DoctorConfigs(bool $activeOnly = true): Collection
    {
        return $this->getGajiDoctorConfigs('gaji_tahap2_dokter_config', $activeOnly);
    }

    public function saveGajiTahap1DoctorConfigs(array $rows): Collection
    {
        $this->saveGajiDoctorConfigs('gaji_tahap1_dokter_config', $rows);

        return $this->getGajiTahap1DoctorConfigs();
    }

    public function saveGajiTahap2DoctorConfigs(array $rows): Collection
    {
        $this->saveGajiDoctorConfigs('gaji_tahap2_dokter_config', $rows);

        return $this->getGajiTahap2DoctorConfigs();
    }

    public function getPayrollRoundingConfig(): array
    {
        if (! Schema::hasTable('penggajian_rounding_config')) {
            return [];
        }

        $row = DB::table('penggajian_rounding_config')
            ->orderBy('id')
            ->first();

        return $row ? $this->payrollRoundingConfigPayload($row) : [];
    }

    public function savePayrollRoundingConfig(array $data): array
    {
        if (! Schema::hasTable('penggajian_rounding_config')) {
            return [];
        }

        $payload = $this->payrollRoundingConfigPayload((object) $data);
        $now = now();
        $id = (int) (DB::table('penggajian_rounding_config')->orderBy('id')->value('id') ?? 0);

        if ($id > 0) {
            DB::table('penggajian_rounding_config')
                ->where('id', $id)
                ->update(array_merge($payload, ['updated_at' => $now]));
        } else {
            DB::table('penggajian_rounding_config')
                ->insert(array_merge($payload, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
        }

        return $this->getPayrollRoundingConfig();
    }

    public function updateOrCreateGajiTahap1(array $data)
    {
        Log::info('Data untuk updateOrCreateGajiTahap1:', $data);

        $values = [
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'],
            'status' => $data['status'],
            'gaji_pokok' => $data['gaji_pokok'],
            'gaji_dibayar' => $data['gaji_dibayar'],
            'tunjangan' => $data['tunjangan'],
        ];

        if (Schema::hasColumn('gaji_tahap1', 'premi')) {
            $values['premi'] = $data['premi'] ?? 0;
        }

        if (Schema::hasColumn('gaji_tahap1', 'premi_breakdown')) {
            $values['premi_breakdown'] = $data['premi_breakdown'] ?? null;
        }

        if (Schema::hasColumn('gaji_tahap1', 'pembulatan')) {
            $values['pembulatan'] = $data['pembulatan'] ?? 0;
        }

        if (Schema::hasColumn('gaji_tahap1', 'total')) {
            $values['total'] = $data['total'] ?? (
                (int) ($data['gaji_dibayar'] ?? 0)
                + (int) ($data['tunjangan'] ?? 0)
                + (int) ($data['premi'] ?? 0)
            );
        }

        if (Schema::hasColumn('gaji_tahap1', 'tunjangan_breakdown')) {
            $values['tunjangan_breakdown'] = $data['tunjangan_breakdown'] ?? null;
        }

        return gajiTahap1Model::updateOrCreate(
            [
                'periode' => $data['periode'],
                'nik' => $data['nik'],
            ],
            $values
        );
    }

    public function deleteGajiTahap1ExceptNik(string $periode, array $niks): int
    {
        $query = gajiTahap1Model::query()->where('periode', $periode);

        if (empty($niks)) {
            return $query->delete();
        }

        return $query
            ->whereNotIn('nik', $niks)
            ->delete();
    }

    public function findGajiTahap1ById($id)
    {
        return gajiTahap1Model::find($id);
    }

    public function findGajiTahap1ByPeriodNik(string $periode, string $nik): ?gajiTahap1Model
    {
        return gajiTahap1Model::query()
            ->where('periode', $periode)
            ->where('nik', $nik)
            ->first();
    }

    public function getGajiTahap2Table($periode): Collection
    {
        return gajiTahap2Model::query()
            ->select([
                'id',
                'periode',
                'nik',
                'nama',
                'jabatan',
                'status',
                'gaji_pokok',
                'gaji_dibayar',
                'total_premi',
                Schema::hasColumn('gaji_tahap2', 'total_potongan')
                    ? 'total_potongan'
                    : DB::raw('0 as total_potongan'),
                Schema::hasColumn('gaji_tahap2', 'pembulatan')
                    ? 'pembulatan'
                    : DB::raw('0 as pembulatan'),
                'total',
                'jumlah_sumber_premi',
                'premi_breakdown',
                Schema::hasColumn('gaji_tahap2', 'potongan_breakdown')
                    ? 'potongan_breakdown'
                    : DB::raw('NULL as potongan_breakdown'),
            ])
            ->where('periode', $periode)
            ->orderBy('nama')
            ->get();
    }

    public function updateOrCreateGajiTahap2(array $data): gajiTahap2Model
    {
        $values = [
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'],
            'status' => $data['status'],
            'gaji_pokok' => $data['gaji_pokok'],
            'gaji_dibayar' => $data['gaji_dibayar'],
            'total_premi' => $data['total_premi'],
            'total' => $data['total'],
            'jumlah_sumber_premi' => $data['jumlah_sumber_premi'],
            'premi_breakdown' => $data['premi_breakdown'],
        ];

        if (Schema::hasColumn('gaji_tahap2', 'total_potongan')) {
            $values['total_potongan'] = $data['total_potongan'] ?? 0;
        }

        if (Schema::hasColumn('gaji_tahap2', 'pembulatan')) {
            $values['pembulatan'] = $data['pembulatan'] ?? 0;
        }

        if (Schema::hasColumn('gaji_tahap2', 'potongan_breakdown')) {
            $values['potongan_breakdown'] = $data['potongan_breakdown'] ?? [];
        }

        return gajiTahap2Model::updateOrCreate(
            [
                'periode' => $data['periode'],
                'nik' => $data['nik'],
            ],
            $values
        );
    }

    public function getGajiTahap1TotalsByNik(string $periode): Collection
    {
        $totalExpression = Schema::hasColumn('gaji_tahap1', 'total')
            ? 'CASE WHEN COALESCE(total, 0) <> 0 THEN total ELSE COALESCE(gaji_dibayar, 0) + COALESCE(tunjangan, 0) END'
            : 'COALESCE(gaji_dibayar, 0) + COALESCE(tunjangan, 0)';

        return gajiTahap1Model::query()
            ->where('periode', $periode)
            ->selectRaw('nik, '.$totalExpression.' as total_tahap1')
            ->pluck('total_tahap1', 'nik');
    }

    public function getPotonganPegawaiForStage2(array $niks): Collection
    {
        $nikList = collect($niks)
            ->filter()
            ->map(fn ($nik) => (string) $nik)
            ->unique()
            ->values();

        if ($nikList->isEmpty()) {
            return collect();
        }

        return potonganPegawaiModel::query()
            ->join('master_potongan', 'master_potongan.id', '=', 'potongan_pegawai.potongan_id')
            ->whereIn('potongan_pegawai.nik', $nikList->all())
            ->select([
                'potongan_pegawai.nik',
                'potongan_pegawai.potongan_id',
                'potongan_pegawai.nominal as nominal_mapping',
                'master_potongan.kode',
                'master_potongan.nama',
                'master_potongan.tipe',
                'master_potongan.nilai',
            ])
            ->get()
            ->groupBy('nik');
    }

    public function replaceGajiTahap2Details(gajiTahap2Model $gaji, Collection $details): void
    {
        $gaji->details()->delete();

        if ($details->isEmpty()) {
            return;
        }

        $now = now();
        $hasSourcePeriode = Schema::hasColumn('gaji_tahap2_detail', 'source_periode');
        $hasSourcePeriodMode = Schema::hasColumn('gaji_tahap2_detail', 'source_period_mode');
        $rows = $details
            ->map(function (array $detail) use ($gaji, $now, $hasSourcePeriode, $hasSourcePeriodMode) {
                $row = [
                    'gaji_tahap2_id' => $gaji->id,
                    'source_key' => $detail['source_key'],
                    'source_label' => $detail['source_label'],
                    'source_table' => $detail['source_table'],
                    'source_id' => $detail['source_id'],
                    'role_label' => $detail['role_label'],
                    'nominal' => $detail['nominal'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasSourcePeriode) {
                    $row['source_periode'] = $detail['source_periode'] ?? null;
                }

                if ($hasSourcePeriodMode) {
                    $row['source_period_mode'] = $detail['source_period_mode'] ?? null;
                }

                return $row;
            })
            ->values();

        gajiTahap2DetailModel::query()->insert($rows->all());
    }

    public function deleteGajiTahap2ExceptNik(string $periode, array $niks): int
    {
        return gajiTahap2Model::query()
            ->where('periode', $periode)
            ->whereNotIn('nik', $niks)
            ->delete();
    }

    public function findGajiTahap2ById($id): ?gajiTahap2Model
    {
        return gajiTahap2Model::query()
            ->with(['details' => fn ($query) => $query->orderBy('source_label')])
            ->find($id);
    }

    public function findGajiTahap2ByPeriodNik(string $periode, string $nik): ?gajiTahap2Model
    {
        if (! Schema::hasTable('gaji_tahap2')) {
            return null;
        }

        return gajiTahap2Model::query()
            ->with(['details' => fn ($query) => $query->orderBy('source_label')])
            ->where('periode', $periode)
            ->where('nik', $nik)
            ->first();
    }

    public function getGajiTahap2RowsByNik(string $periode, array $niks): Collection
    {
        if (! Schema::hasTable('gaji_tahap2')) {
            return collect();
        }

        $nikList = collect($niks)
            ->filter()
            ->map(fn ($nik) => (string) $nik)
            ->unique()
            ->values();

        if ($nikList->isEmpty()) {
            return collect();
        }

        return gajiTahap2Model::query()
            ->where('periode', $periode)
            ->whereIn('nik', $nikList->all())
            ->get()
            ->keyBy(fn ($row) => (string) $row->nik);
    }

    public function getPremiDokterDetailCountsByIds(array $ids): Collection
    {
        if (! $this->hasTables(['generate_premi_dokter_detail'])
            || ! $this->hasColumns('generate_premi_dokter_detail', ['id', 'jumlah_data', 'jumlah_pasien'])) {
            return collect();
        }

        $idList = collect($ids)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($idList->isEmpty()) {
            return collect();
        }

        return DB::table('generate_premi_dokter_detail')
            ->whereIn('id', $idList->all())
            ->select([
                'id',
                'jumlah_data',
                'jumlah_pasien',
            ])
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->id => [
                    'jumlah_data' => (int) $row->jumlah_data,
                    'jumlah_pasien' => (int) $row->jumlah_pasien,
                ],
            ]);
    }

    public function getPremiDokterDetailCountsByPeriodNik(
        string $periode,
        string $nik,
        array $premiumTypes = []
    ): Collection {
        if (! $this->hasTables(['generate_premi_dokter', 'generate_premi_dokter_detail'])
            || ! $this->hasColumns('generate_premi_dokter', ['id', 'periode', 'jenis_premi_dokter'])
            || ! $this->hasColumns('generate_premi_dokter_detail', ['generate_premi_dokter_id', 'kd_dokter', 'jumlah_data', 'jumlah_pasien'])) {
            return collect();
        }

        $premiumTypes = collect($premiumTypes)
            ->filter()
            ->map(fn ($type) => strtolower((string) $type))
            ->unique()
            ->values();

        $query = DB::table('generate_premi_dokter_detail as d')
            ->join('generate_premi_dokter as h', 'h.id', '=', 'd.generate_premi_dokter_id');

        return $this->applyLockedSource($query, 'generate_premi_dokter', 'h')
            ->where('h.periode', $periode)
            ->where('d.kd_dokter', $nik)
            ->when($premiumTypes->isNotEmpty(), function ($query) use ($premiumTypes) {
                $query->whereIn(DB::raw('LOWER(h.jenis_premi_dokter)'), $premiumTypes->all());
            })
            ->selectRaw('LOWER(h.jenis_premi_dokter) as jenis_premi_dokter')
            ->selectRaw('SUM(d.jumlah_data) as jumlah_data')
            ->selectRaw('SUM(d.jumlah_pasien) as jumlah_pasien')
            ->groupBy(DB::raw('LOWER(h.jenis_premi_dokter)'))
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->jenis_premi_dokter => [
                    'jumlah_data' => (int) $row->jumlah_data,
                    'jumlah_pasien' => (int) $row->jumlah_pasien,
                ],
            ]);
    }

    public function getGajiTahap2DetailsByPeriod(string $periode): Collection
    {
        return gajiTahap2DetailModel::query()
            ->join('gaji_tahap2', 'gaji_tahap2.id', '=', 'gaji_tahap2_detail.gaji_tahap2_id')
            ->select([
                'gaji_tahap2.periode',
                'gaji_tahap2.nik',
                'gaji_tahap2.nama',
                'gaji_tahap2.jabatan',
                'gaji_tahap2.status',
                'gaji_tahap2_detail.source_label',
                'gaji_tahap2_detail.role_label',
                'gaji_tahap2_detail.nominal',
                Schema::hasColumn('gaji_tahap2_detail', 'source_periode')
                    ? 'gaji_tahap2_detail.source_periode'
                    : DB::raw('NULL as source_periode'),
                Schema::hasColumn('gaji_tahap2_detail', 'source_period_mode')
                    ? 'gaji_tahap2_detail.source_period_mode'
                    : DB::raw('NULL as source_period_mode'),
            ])
            ->where('gaji_tahap2.periode', $periode)
            ->orderBy('gaji_tahap2.nama')
            ->orderBy('gaji_tahap2_detail.source_label')
            ->get();
    }

    public function getGajiTahap2GeneratorReadiness(string $periode): array
    {
        $items = collect($this->stage2GeneratorDefinitions())
            ->map(fn (array $definition) => $this->generatorReadiness($definition, $periode))
            ->values();

        $notReady = $items->reject(fn (array $item) => $item['ready'])->values();
        $ready = $notReady->isEmpty();

        return [
            'periode' => $periode,
            'ready' => $ready,
            'total_required' => $items->count(),
            'total_ready' => $items->where('ready', true)->count(),
            'total_generated' => (int) $items->sum('generated_count'),
            'total_locked' => (int) $items->sum('locked_count'),
            'total_unlocked' => (int) $items->sum('unlocked_count'),
            'not_ready_count' => $notReady->count(),
            'not_ready_labels' => $notReady->pluck('label')->values()->all(),
            'items' => $items->all(),
            'message' => $ready
                ? 'Semua generator tahap 2 sudah digenerate dan dikunci.'
                : 'Lengkapi dan kunci semua generator tahap 2: '.$notReady->pluck('label')->implode(', ').'.',
        ];
    }

    public function collectPremiTahap2ByPeriod(string $periode, Collection $eligibleNik): Collection
    {
        $niks = $eligibleNik
            ->filter()
            ->map(fn ($nik) => (string) $nik)
            ->unique()
            ->values();

        if ($niks->isEmpty()) {
            return collect();
        }

        $rows = collect();
        $nikList = $niks->all();

        $this->appendPegawaiDetailPremium(
            $rows,
            $nikList,
            $periode,
            'generate_laboratorium',
            'generate_laboratorium_detail',
            'generate_laboratorium_id',
            'Laboratorium',
            'jenis_laboratorium'
        );

        $this->appendPegawaiDetailPremium(
            $rows,
            $nikList,
            $periode,
            'generate_radiologi',
            'generate_radiologi_detail',
            'generate_radiologi_id',
            'Radiologi',
            'jenis_radiologi'
        );

        $this->appendPegawaiDetailPremium(
            $rows,
            $nikList,
            $periode,
            'generate_operasi',
            'generate_operasi_details',
            'generate_operasi_id',
            'Operasi',
            'jenis_operasi'
        );

        $this->appendPegawaiDetailPremium(
            $rows,
            $nikList,
            $periode,
            'generate_vk',
            'generate_vk_detail',
            'generate_vk_id',
            'VK',
            'jenis_vk'
        );

        $this->appendPegawaiDetailPremium(
            $rows,
            $nikList,
            $periode,
            'generate_apotek',
            'generate_apotek_recipient',
            'generate_apotek_id',
            'Apotek',
            'jenis_apotek'
        );

        $this->appendPegawaiDetailPremium(
            $rows,
            $nikList,
            $periode,
            'generate_gizi',
            'generate_gizi_recipient',
            'generate_gizi_id',
            'Gizi',
            'jenis_gizi'
        );

        $this->appendCasemixPremium($rows, $nikList, $periode);
        $this->appendFisioPremium($rows, $nikList, $periode);
        $this->appendDriverPremium($rows, $nikList, $periode);
        $this->appendNonMedisPremium($rows, $nikList, $periode);
        $this->appendTindakanMedisPremium($rows, $nikList, $periode);
        $this->appendPremiBersamaPremium($rows, $nikList, $periode);
        $this->appendPremiDokterPremium($rows, $nikList, $periode);

        return $rows
            ->filter(fn (array $row) => $row['nominal'] > 0)
            ->values();
    }

    public function collectPremiDokterByPeriod(string $periode, Collection $eligibleNik): Collection
    {
        $niks = $eligibleNik
            ->filter()
            ->map(fn ($nik) => (string) $nik)
            ->unique()
            ->values();

        if ($niks->isEmpty()) {
            return collect();
        }

        $rows = collect();

        $this->appendPremiDokterPremium($rows, $niks->all(), $periode);

        return $rows
            ->filter(fn (array $row) => $row['nominal'] > 0)
            ->values();
    }

    private function appendPegawaiDetailPremium(
        Collection $rows,
        array $niks,
        string $periode,
        string $headerTable,
        string $detailTable,
        string $foreignKey,
        string $label,
        string $typeColumn
    ): void {
        if (
            ! $this->hasTables([$headerTable, $detailTable])
            || ! $this->hasColumns($headerTable, ['periode', $typeColumn])
            || ! $this->hasColumns($detailTable, [$foreignKey, 'pegawai_id', 'role_label', 'total_received'])
        ) {
            return;
        }

        $query = DB::table($detailTable.' as d')
            ->join($headerTable.' as h', 'h.id', '=', 'd.'.$foreignKey);
        $select = [
            'd.id as source_id',
            'd.pegawai_id as nik',
            'd.role_label',
            'd.total_received as nominal',
            'h.'.$typeColumn.' as source_type',
            Schema::hasColumn($headerTable, 'source_periode')
                ? 'h.source_periode'
                : DB::raw('NULL as source_periode'),
            Schema::hasColumn($headerTable, 'source_period_mode')
                ? 'h.source_period_mode'
                : DB::raw('NULL as source_period_mode'),
            Schema::hasColumn($headerTable, 'config_snapshot')
                ? 'h.config_snapshot'
                : DB::raw('NULL as config_snapshot'),
        ];

        $this->applyLockedSource($query, $headerTable, 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.pegawai_id', $niks)
            ->where('d.total_received', '>', 0)
            ->select($select)
            ->orderBy('d.pegawai_id')
            ->get()
            ->each(function ($row) use ($rows, $detailTable, $label, $periode) {
                $sourceType = strtoupper((string) ($row->source_type ?? ''));
                $roleLabel = (string) ($row->role_label ?? $label);

                $rows->push([
                    'nik' => (string) $row->nik,
                    'source_key' => str($label.' '.$sourceType)->slug('_')->toString(),
                    'source_label' => trim($label.' '.$sourceType.' - '.$roleLabel, ' -'),
                    'source_table' => $detailTable,
                    'source_id' => (int) $row->source_id,
                    'role_label' => $roleLabel,
                    'source_periode' => $this->resolvePremiumSourcePeriod($periode, $row->source_type ?? null, $row),
                    'source_period_mode' => $this->resolvePremiumSourcePeriodMode($periode, $row->source_type ?? null, $row),
                    'nominal' => (int) round((float) $row->nominal),
                ]);
            });
    }

    private function appendCasemixPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['generate_casemix', 'generate_casemix_detail'])) {
            return;
        }

        $query = DB::table('generate_casemix_detail as d')
            ->join('generate_casemix as h', 'h.id', '=', 'd.generate_casemix_id');

        $this->applyLockedSource($query, 'generate_casemix', 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.pegawai_id', $niks)
            ->where('d.total_received', '>', 0)
            ->select([
                'd.id as source_id',
                'd.pegawai_id as nik',
                'd.role_label',
                'd.total_received as nominal',
                'h.config_snapshot',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'casemix',
                'source_label' => 'Casemix - '.($row->role_label ?: 'Penerima'),
                'source_table' => 'generate_casemix_detail',
                'source_id' => (int) $row->source_id,
                'role_label' => $row->role_label ?: 'Penerima',
                'source_periode' => $this->resolvePremiumSourcePeriod($periode, 'bpjs', $row),
                'source_period_mode' => $this->resolvePremiumSourcePeriodMode($periode, 'bpjs', $row),
                'nominal' => (int) round((float) $row->nominal),
            ]));
    }

    private function appendFisioPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['generate_premi_fisio', 'generate_premi_fisio_detail'])) {
            return;
        }

        $query = DB::table('generate_premi_fisio_detail as d')
            ->join('generate_premi_fisio as h', 'h.id', '=', 'd.generate_premi_fisio_id');

        $this->applyLockedSource($query, 'generate_premi_fisio', 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.pegawai_id', $niks)
            ->where('d.total_received', '>', 0)
            ->select([
                'd.id as source_id',
                'd.pegawai_id as nik',
                'd.role_label',
                'd.total_received as nominal',
                'h.periode',
                'h.jenis_fisio',
                'h.nama_tindakan',
                'h.config_snapshot',
            ])
            ->get()
            ->each(function ($row) use ($rows) {
                $rows->push([
                    'nik' => (string) $row->nik,
                    'source_key' => 'fisio_'.strtolower((string) $row->jenis_fisio),
                    'source_label' => trim('Fisio '.strtoupper((string) $row->jenis_fisio).' - '.($row->nama_tindakan ?: $row->role_label), ' -'),
                    'source_table' => 'generate_premi_fisio_detail',
                    'source_id' => (int) $row->source_id,
                    'role_label' => $row->role_label ?: 'Petugas',
                    'source_periode' => $this->resolvePremiumSourcePeriod($row->periode ?? '', $row->jenis_fisio ?? null, $row),
                    'source_period_mode' => $this->resolvePremiumSourcePeriodMode($row->periode ?? '', $row->jenis_fisio ?? null, $row),
                    'nominal' => (int) round((float) $row->nominal),
                ]);
            });
    }

    private function appendDriverPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! Schema::hasTable('generate_premi_driver')) {
            return;
        }

        $query = DB::table('generate_premi_driver');

        $this->applyLockedSource($query, 'generate_premi_driver')
            ->where('periode', $periode)
            ->whereIn('pegawai_id', $niks)
            ->where('total_premi_pegawai', '>', 0)
            ->select([
                'id as source_id',
                'periode',
                'pegawai_id as nik',
                'total_premi_pegawai as nominal',
                'config_snapshot',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'driver',
                'source_label' => 'Driver',
                'source_table' => 'generate_premi_driver',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Driver',
                'source_periode' => $this->resolvePremiumSourcePeriod($periode, 'umum', $row),
                'source_period_mode' => $this->resolvePremiumSourcePeriodMode($periode, 'umum', $row),
                'nominal' => (int) round((float) $row->nominal),
            ]));
    }

    private function appendNonMedisPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['premi_pelayanan_non_medis', 'premi_pelayanan_non_medis_distribution'])) {
            return;
        }

        $eligibleNik = array_flip(
            collect($niks)
                ->map(fn ($nik) => (string) $nik)
                ->all()
        );
        $activePremiIds = $this->activeNonMedisPremiIds();
        $query = DB::table('premi_pelayanan_non_medis_distribution as d')
            ->join('premi_pelayanan_non_medis as h', 'h.id', '=', 'd.premi_pelayanan_non_medis_id');

        $this->applyLockedSource($query, 'premi_pelayanan_non_medis', 'h')
            ->where('h.periode', $periode)
            ->when(! empty($activePremiIds), function ($query) use ($activePremiIds) {
                $query->where(function ($where) use ($activePremiIds) {
                    foreach ($activePremiIds as $jenis => $premiId) {
                        $where->orWhere(function ($typed) use ($jenis, $premiId) {
                            $typed
                                ->where('h.jenis_pelayanan', $jenis)
                                ->where('h.jnsPremi_id', $premiId);
                        });
                    }
                });
            })
            ->where('d.total_diterima', '>', 0)
            ->select([
                'd.id as source_id',
                'd.nik',
                'd.distribution_mode',
                'h.id as header_id',
                'h.periode',
                'h.jenis_pelayanan',
                'h.total_final',
            ])
            ->orderBy('h.id')
            ->orderBy('d.id')
            ->get()
            ->groupBy('header_id')
            ->each(function (Collection $headerRows) use ($rows, $eligibleNik) {
                $first = $headerRows->first();
                $recipients = $headerRows
                    ->unique(fn ($row) => (string) $row->nik)
                    ->values();
                $count = $recipients->count();

                if ($count === 0) {
                    return;
                }

                $mode = in_array((string) $first->distribution_mode, ['split_evenly', 'full_amount'], true)
                    ? (string) $first->distribution_mode
                    : 'split_evenly';
                $totalCents = (int) round(((float) $first->total_final) * 100);
                $baseCents = $mode === 'full_amount'
                    ? $totalCents
                    : intdiv($totalCents, $count);
                $remainder = $mode === 'full_amount'
                    ? 0
                    : $totalCents % $count;

                $recipients->each(function ($row, int $index) use (
                    $rows,
                    $first,
                    $eligibleNik,
                    $baseCents,
                    $remainder
                ) {
                    if (! isset($eligibleNik[(string) $row->nik])) {
                        return;
                    }

                    $amountCents = $baseCents + ($index < $remainder ? 1 : 0);

                    $rows->push([
                        'nik' => (string) $row->nik,
                        'source_key' => 'pelayanan_non_medis_'.strtolower((string) $first->jenis_pelayanan),
                        'source_label' => 'Pelayanan Non Medis '.strtoupper((string) $first->jenis_pelayanan),
                        'source_table' => 'premi_pelayanan_non_medis_distribution',
                        'source_id' => (int) $row->source_id,
                        'role_label' => 'Penerima',
                        'source_periode' => $this->resolvePremiumSourcePeriod(
                            (string) $first->periode,
                            $first->jenis_pelayanan ?? null,
                            $first
                        ),
                        'source_period_mode' => $this->resolvePremiumSourcePeriodMode(
                            (string) $first->periode,
                            $first->jenis_pelayanan ?? null,
                            $first
                        ),
                        'nominal' => (int) round($amountCents / 100),
                    ]);
                });
            });
    }

    private function activeNonMedisPremiIds(): array
    {
        if (! Schema::hasTable('premi_pelayanan_non_medis_config')) {
            return [];
        }

        $config = DB::table('premi_pelayanan_non_medis_config')->first();

        if (! $config) {
            return [];
        }

        $legacyPremiId = (int) ($config->jnsPremi_id ?? 0);

        return collect([
            'umum' => (int) (data_get($config, 'jnsPremi_umum_id') ?: $legacyPremiId),
            'bpjs' => (int) (data_get($config, 'jnsPremi_bpjs_id') ?: $legacyPremiId),
        ])
            ->filter(fn (int $premiId) => $premiId > 0)
            ->all();
    }

    private function appendTindakanMedisPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['generate_tindakan_medis', 'generate_tindakan_medis_distribution'])) {
            return;
        }

        $nominalColumn = Schema::hasColumn('generate_tindakan_medis_distribution', 'total_dasar')
            ? 'total_dasar'
            : 'total_diterima';

        $query = DB::table('generate_tindakan_medis_distribution as d')
            ->join('generate_tindakan_medis as h', 'h.id', '=', 'd.generate_tindakan_medis_id');

        $this->applyLockedSource($query, 'generate_tindakan_medis', 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.nik', $niks)
            ->where('d.'.$nominalColumn, '>', 0)
            ->select([
                'd.id as source_id',
                'd.nik',
                'd.'.$nominalColumn.' as nominal',
                'h.periode',
                'h.source_periode',
                'h.bpjs_source_mode',
                'h.jenis_pelayanan',
                'h.nama_premi',
                'h.config_snapshot',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'tindakan_medis_'.strtolower((string) $row->jenis_pelayanan),
                'source_label' => trim('Tindakan Medis '.strtoupper((string) $row->jenis_pelayanan).' - '.($row->nama_premi ?: ''), ' -'),
                'source_table' => 'generate_tindakan_medis_distribution',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Penerima',
                'source_periode' => $this->resolvePremiumSourcePeriod((string) $row->periode, $row->jenis_pelayanan ?? null, $row),
                'source_period_mode' => $this->resolvePremiumSourcePeriodMode((string) $row->periode, $row->jenis_pelayanan ?? null, $row),
                'nominal' => (int) round((float) $row->nominal),
            ]));
    }

    private function appendPremiBersamaPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['generate_premi_bersama', 'generate_premi_bersama_distribution'])) {
            return;
        }

        $query = DB::table('generate_premi_bersama_distribution as d')
            ->join('generate_premi_bersama as h', 'h.id', '=', 'd.generate_premi_bersama_id');

        $this->applyLockedSource($query, 'generate_premi_bersama', 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.nik', $niks)
            ->where('d.total_received', '>', 0)
            ->select([
                'd.id as source_id',
                'd.nik',
                'd.total_received as nominal',
                'h.periode',
                'h.source_periode',
                'h.bpjs_source_mode',
                'h.bpjs_source_periode',
                'h.jenis_pelayanan',
                'h.nama_premi',
                'h.config_snapshot',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'premi_bersama_'.strtolower((string) $row->jenis_pelayanan),
                'source_label' => trim('Premi Bersama '.strtoupper((string) $row->jenis_pelayanan).' - '.($row->nama_premi ?: ''), ' -'),
                'source_table' => 'generate_premi_bersama_distribution',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Penerima',
                'source_periode' => $this->resolvePremiumSourcePeriod((string) $row->periode, $row->jenis_pelayanan ?? null, $row),
                'source_period_mode' => $this->resolvePremiumSourcePeriodMode((string) $row->periode, $row->jenis_pelayanan ?? null, $row),
                'nominal' => (int) round((float) $row->nominal),
            ]));
    }

    private function appendPremiDokterPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['generate_premi_dokter', 'generate_premi_dokter_detail'])) {
            return;
        }

        $query = DB::table('generate_premi_dokter_detail as d')
            ->join('generate_premi_dokter as h', 'h.id', '=', 'd.generate_premi_dokter_id');

        $this->applyLockedSource($query, 'generate_premi_dokter', 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.kd_dokter', $niks)
            ->where('d.total_premi', '>', 0)
            ->select([
                'd.id as source_id',
                'd.kd_dokter as nik',
                'd.kategori',
                'd.jumlah_data',
                'd.jumlah_pasien',
                'd.total_premi as nominal',
                'h.periode',
                'h.source_periode',
                'h.source_period_mode',
                'h.jenis_premi_dokter',
                'h.jenis_pelayanan',
                'h.config_snapshot',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'premi_dokter_'.strtolower((string) $row->jenis_premi_dokter).'_'.strtolower((string) $row->jenis_pelayanan),
                'source_label' => trim('Premi Dokter '.strtoupper((string) $row->jenis_pelayanan).' - '.str_replace('_', ' ', (string) $row->jenis_premi_dokter).' - '.($row->kategori ?: ''), ' -'),
                'source_table' => 'generate_premi_dokter_detail',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Dokter',
                'source_premium_type' => (string) $row->jenis_premi_dokter,
                'source_jumlah_data' => (int) $row->jumlah_data,
                'source_jumlah_pasien' => (int) $row->jumlah_pasien,
                'source_periode' => $this->resolvePremiumSourcePeriod((string) $row->periode, $row->jenis_pelayanan ?? null, $row),
                'source_period_mode' => $this->resolvePremiumSourcePeriodMode((string) $row->periode, $row->jenis_pelayanan ?? null, $row),
                'nominal' => (int) round((float) $row->nominal),
            ]));
    }

    private function stage2GeneratorDefinitions(): array
    {
        return [
            ['key' => 'laboratorium', 'label' => 'Laboratorium', 'table' => 'generate_laboratorium', 'type_column' => 'jenis_laboratorium'],
            ['key' => 'radiologi', 'label' => 'Radiologi', 'table' => 'generate_radiologi', 'type_column' => 'jenis_radiologi'],
            ['key' => 'operasi', 'label' => 'Operasi', 'table' => 'generate_operasi', 'type_column' => 'jenis_operasi'],
            ['key' => 'vk', 'label' => 'VK', 'table' => 'generate_vk', 'type_column' => 'jenis_vk'],
            ['key' => 'apotek', 'label' => 'Apotek', 'table' => 'generate_apotek', 'type_column' => 'jenis_apotek'],
            ['key' => 'gizi', 'label' => 'Gizi', 'table' => 'generate_gizi', 'type_column' => 'jenis_gizi'],
            ['key' => 'casemix', 'label' => 'Casemix', 'table' => 'generate_casemix'],
            ['key' => 'fisio', 'label' => 'Fisioterapi', 'table' => 'generate_premi_fisio', 'type_column' => 'jenis_fisio'],
            ['key' => 'driver', 'label' => 'Driver', 'table' => 'generate_premi_driver'],
            ['key' => 'non_medis', 'label' => 'Pelayanan Non Medis', 'table' => 'premi_pelayanan_non_medis', 'type_column' => 'jenis_pelayanan'],
            ['key' => 'tindakan_medis', 'label' => 'Tindakan Medis', 'table' => 'generate_tindakan_medis', 'type_column' => 'jenis_pelayanan'],
            ['key' => 'premi_bersama', 'label' => 'Premi Bersama', 'table' => 'generate_premi_bersama', 'type_column' => 'jenis_pelayanan'],
            ['key' => 'premi_dokter', 'label' => 'Premi Dokter', 'table' => 'generate_premi_dokter', 'type_column' => 'jenis_pelayanan'],
        ];
    }

    private function generatorReadiness(array $definition, string $periode): array
    {
        $table = $definition['table'];

        if (! Schema::hasTable($table)) {
            return $this->generatorReadinessPayload($definition, 'missing', false, 'Tabel generator belum tersedia.');
        }

        $missingColumns = collect(['periode', 'is_locked'])
            ->reject(fn (string $column) => Schema::hasColumn($table, $column))
            ->values();

        if ($missingColumns->isNotEmpty()) {
            return $this->generatorReadinessPayload(
                $definition,
                'missing',
                false,
                'Kolom '.($missingColumns->implode(', ')).' belum tersedia.'
            );
        }

        if ($this->generatorHasServiceTypes($definition)) {
            return $this->typedGeneratorReadiness($definition, $periode);
        }

        $query = fn () => DB::table($table)->where('periode', $periode);
        $generatedCount = (int) $query()->count();
        $lockedCount = (int) $query()->where('is_locked', true)->count();
        $unlockedCount = max(0, $generatedCount - $lockedCount);
        $latestGeneratedAt = null;
        $latestLockedAt = null;

        foreach (['updated_at', 'created_at'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $latestGeneratedAt = $query()->max($column);
                break;
            }
        }

        if (Schema::hasColumn($table, 'locked_at')) {
            $latestLockedAt = $query()->where('is_locked', true)->max('locked_at');
        }

        if ($generatedCount <= 0) {
            return $this->generatorReadinessPayload(
                $definition,
                'missing',
                false,
                'Belum digenerate.',
                $generatedCount,
                $lockedCount,
                $unlockedCount,
                $latestGeneratedAt,
                $latestLockedAt
            );
        }

        if ($unlockedCount > 0) {
            return $this->generatorReadinessPayload(
                $definition,
                'unlocked',
                false,
                $unlockedCount.' data belum dikunci.',
                $generatedCount,
                $lockedCount,
                $unlockedCount,
                $latestGeneratedAt,
                $latestLockedAt
            );
        }

        return $this->generatorReadinessPayload(
            $definition,
            'ready',
            true,
            'Sudah digenerate dan dikunci.',
            $generatedCount,
            $lockedCount,
            $unlockedCount,
            $latestGeneratedAt,
            $latestLockedAt
        );
    }

    private function typedGeneratorReadiness(array $definition, string $periode): array
    {
        $table = $definition['table'];
        $typeColumn = $definition['type_column'];

        if (! Schema::hasColumn($table, $typeColumn)) {
            return $this->generatorReadinessPayload(
                $definition,
                'missing',
                false,
                'Kolom '.$typeColumn.' belum tersedia.'
            );
        }

        $typeItems = collect($this->stage2GeneratorRequiredTypes())
            ->map(fn (array $type) => $this->generatorTypeReadiness($definition, $periode, $type))
            ->values();
        $generatedCount = (int) $typeItems->sum('generated_count');
        $lockedCount = (int) $typeItems->sum('locked_count');
        $unlockedCount = (int) $typeItems->sum('unlocked_count');
        $notReadyTypes = $typeItems->reject(fn (array $item) => $item['ready'])->values();
        $ready = $notReadyTypes->isEmpty();
        $state = $ready
            ? 'ready'
            : ($notReadyTypes->contains(fn (array $item) => $item['state'] === 'unlocked') ? 'unlocked' : 'missing');
        $note = $ready
            ? 'Jenis UMUM dan BPJS sudah digenerate dan dikunci.'
            : 'Jenis belum ready: '.$notReadyTypes->pluck('label')->implode(', ').'.';

        return $this->generatorReadinessPayload(
            $definition,
            $state,
            $ready,
            $note,
            $generatedCount,
            $lockedCount,
            $unlockedCount,
            $typeItems->pluck('latest_generated_at')->filter()->max(),
            $typeItems->pluck('latest_locked_at')->filter()->max(),
            $typeItems->all()
        );
    }

    private function generatorTypeReadiness(array $definition, string $periode, array $type): array
    {
        $table = $definition['table'];
        $typeColumn = $definition['type_column'];
        $query = fn () => DB::table($table)
            ->where('periode', $periode)
            ->whereRaw('LOWER('.$typeColumn.') = ?', [$type['value']]);

        $generatedCount = (int) $query()->count();
        $lockedCount = (int) $query()->where('is_locked', true)->count();
        $unlockedCount = max(0, $generatedCount - $lockedCount);
        $latestGeneratedAt = null;
        $latestLockedAt = null;

        foreach (['updated_at', 'created_at'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $latestGeneratedAt = $query()->max($column);
                break;
            }
        }

        if (Schema::hasColumn($table, 'locked_at')) {
            $latestLockedAt = $query()->where('is_locked', true)->max('locked_at');
        }

        if ($generatedCount <= 0) {
            $state = 'missing';
            $ready = false;
            $note = 'Belum digenerate.';
        } elseif ($unlockedCount > 0) {
            $state = 'unlocked';
            $ready = false;
            $note = $unlockedCount.' data belum dikunci.';
        } else {
            $state = 'ready';
            $ready = true;
            $note = 'Sudah digenerate dan dikunci.';
        }

        return [
            'key' => $type['value'],
            'label' => $type['label'],
            'state' => $state,
            'ready' => $ready,
            'generated_count' => $generatedCount,
            'locked_count' => $lockedCount,
            'unlocked_count' => $unlockedCount,
            'latest_generated_at' => $latestGeneratedAt,
            'latest_locked_at' => $latestLockedAt,
            'note' => $note,
        ];
    }

    private function generatorReadinessPayload(
        array $definition,
        string $state,
        bool $ready,
        string $note,
        int $generatedCount = 0,
        int $lockedCount = 0,
        int $unlockedCount = 0,
        ?string $latestGeneratedAt = null,
        ?string $latestLockedAt = null,
        array $typeItems = []
    ): array {
        return [
            'key' => $definition['key'],
            'label' => $definition['label'],
            'table' => $definition['table'],
            'type_column' => $definition['type_column'] ?? null,
            'has_service_types' => $this->generatorHasServiceTypes($definition),
            'state' => $state,
            'ready' => $ready,
            'generated_count' => $generatedCount,
            'locked_count' => $lockedCount,
            'unlocked_count' => $unlockedCount,
            'latest_generated_at' => $latestGeneratedAt,
            'latest_locked_at' => $latestLockedAt,
            'note' => $note,
            'type_items' => $typeItems,
        ];
    }

    private function generatorHasServiceTypes(array $definition): bool
    {
        return filled($definition['type_column'] ?? null);
    }

    private function stage2GeneratorRequiredTypes(): array
    {
        return [
            ['value' => 'umum', 'label' => 'UMUM'],
            ['value' => 'bpjs', 'label' => 'BPJS'],
        ];
    }

    private function applyLockedSource($query, string $table, ?string $alias = null)
    {
        if (Schema::hasColumn($table, 'is_locked')) {
            $query->where(($alias ?: $table).'.is_locked', true);
        }

        return $query;
    }

    private function getGajiDoctorConfigs(string $table, bool $activeOnly = true): Collection
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('nm_dokter')
            ->get()
            ->map(function ($row) {
                $row->include_salary = (bool) $row->include_salary;
                $row->is_active = (bool) $row->is_active;
                $row->premium_types = collect(json_decode($row->premium_types ?: '[]', true))
                    ->filter()
                    ->map(fn ($type) => (string) $type)
                    ->unique()
                    ->values()
                    ->all();

                return $row;
            });
    }

    private function saveGajiDoctorConfigs(string $table, array $rows): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::transaction(function () use ($table, $rows) {
            DB::table($table)->delete();

            if (empty($rows)) {
                return;
            }

            $now = now();
            DB::table($table)->insert(
                collect($rows)
                    ->map(fn (array $row) => [
                        'kd_dokter' => $row['kd_dokter'],
                        'nm_dokter' => $row['nm_dokter'],
                        'kd_sps' => $row['kd_sps'] ?? null,
                        'nm_sps' => $row['nm_sps'] ?? null,
                        'include_salary' => (bool) ($row['include_salary'] ?? false),
                        'premium_types' => json_encode(array_values($row['premium_types'] ?? [])),
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all()
            );
        });
    }

    private function payrollRoundingConfigPayload(object $row): array
    {
        return [
            'premium_received_enabled' => (bool) ($row->premium_received_enabled ?? false),
            'premium_received_base' => max(1, (int) ($row->premium_received_base ?? 1000)),
            'premium_received_mode' => $this->normalizeRoundingMode($row->premium_received_mode ?? null),
            'stage1_total_enabled' => (bool) ($row->stage1_total_enabled ?? true),
            'stage1_total_base' => max(1, (int) ($row->stage1_total_base ?? 1000)),
            'stage1_total_mode' => $this->normalizeRoundingMode($row->stage1_total_mode ?? null),
            'stage2_total_enabled' => (bool) ($row->stage2_total_enabled ?? true),
            'stage2_total_base' => max(1, (int) ($row->stage2_total_base ?? 1000)),
            'stage2_total_mode' => $this->normalizeRoundingMode($row->stage2_total_mode ?? null),
        ];
    }

    private function normalizeRoundingMode(?string $mode): string
    {
        return in_array($mode, ['nearest', 'up', 'down'], true)
            ? $mode
            : 'up';
    }

    private function resolvePremiumSourcePeriod(string $periode, ?string $jenis, ?object $row = null): ?string
    {
        if ($periode === '') {
            return null;
        }

        $jenis = strtolower((string) $jenis);
        $snapshot = $this->decodeSnapshot($row->config_snapshot ?? null);

        if ($jenis === 'bpjs') {
            $bpjsSource = $row->bpjs_source_periode
                ?? data_get($snapshot, 'bpjs_source_periode')
                ?? data_get($snapshot, 'raw_snapshot.bpjs_source_periode');

            if (filled($bpjsSource)) {
                return (string) $bpjsSource;
            }
        }

        $source = $row->source_periode
            ?? data_get($snapshot, 'source_periode')
            ?? data_get($snapshot, 'raw_snapshot.source_periode');

        if (filled($source)) {
            return (string) $source;
        }

        return PremiSourcePeriod::resolve(
            $periode,
            $jenis === 'bpjs' ? 'bpjs' : 'umum',
            $this->resolvePremiumSourcePeriodMode($periode, $jenis, $row)
        );
    }

    private function resolvePremiumSourcePeriodMode(string $periode, ?string $jenis, ?object $row = null): ?string
    {
        if ($periode === '') {
            return null;
        }

        $jenis = strtolower((string) $jenis);
        $snapshot = $this->decodeSnapshot($row->config_snapshot ?? null);
        $mode = $jenis === 'bpjs'
            ? ($row->bpjs_source_mode
                ?? data_get($snapshot, 'bpjs_source_mode')
                ?? data_get($snapshot, 'raw_snapshot.bpjs_source_mode')
                ?? $row->source_period_mode
                ?? data_get($snapshot, 'source_period_mode'))
            : ($row->source_period_mode ?? data_get($snapshot, 'source_period_mode'));

        return PremiSourcePeriod::normalizeMode(
            filled($mode) ? (string) $mode : null,
            $jenis === 'bpjs' ? 'bpjs' : 'umum'
        );
    }

    private function decodeSnapshot($snapshot): array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        if (is_string($snapshot) && trim($snapshot) !== '') {
            $decoded = json_decode($snapshot, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
