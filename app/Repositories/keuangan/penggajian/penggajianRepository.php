<?php

namespace App\Repositories\keuangan\penggajian;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gajiTahap1Model;
use App\Models\dbSimrs\gajiTahap2DetailModel;
use App\Models\dbSimrs\gajiTahap2Model;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\tunjanganPegawaiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class penggajianRepository
{
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
        return gajiTahap1Model::select('id', 'nik', 'nama', 'jabatan', 'status', 'gaji_pokok', 'gaji_dibayar', 'tunjangan', 'periode')
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

    public function getGajiTahap2DoctorConfigs(bool $activeOnly = true): Collection
    {
        if (! Schema::hasTable('gaji_tahap2_dokter_config')) {
            return collect();
        }

        return DB::table('gaji_tahap2_dokter_config')
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

    public function saveGajiTahap2DoctorConfigs(array $rows): Collection
    {
        if (! Schema::hasTable('gaji_tahap2_dokter_config')) {
            return collect();
        }

        DB::transaction(function () use ($rows) {
            DB::table('gaji_tahap2_dokter_config')->delete();

            if (empty($rows)) {
                return;
            }

            $now = now();
            DB::table('gaji_tahap2_dokter_config')->insert(
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

        return $this->getGajiTahap2DoctorConfigs();
    }

    public function updateOrCreateGajiTahap1(array $data)
    {
        Log::info('Data untuk updateOrCreateGajiTahap1:', $data);

        return gajiTahap1Model::updateOrCreate(
            [
                'periode' => $data['periode'],
                'nik' => $data['nik'],
            ],
            [
                'nama' => $data['nama'],
                'jabatan' => $data['jabatan'],
                'status' => $data['status'],
                'gaji_pokok' => $data['gaji_pokok'],
                'gaji_dibayar' => $data['gaji_dibayar'],
                'tunjangan' => $data['tunjangan'],
            ]
        );
    }

    public function findGajiTahap1ById($id)
    {
        return gajiTahap1Model::find($id);
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
                'total',
                'jumlah_sumber_premi',
                'premi_breakdown',
            ])
            ->where('periode', $periode)
            ->orderBy('nama')
            ->get();
    }

    public function updateOrCreateGajiTahap2(array $data): gajiTahap2Model
    {
        return gajiTahap2Model::updateOrCreate(
            [
                'periode' => $data['periode'],
                'nik' => $data['nik'],
            ],
            [
                'nama' => $data['nama'],
                'jabatan' => $data['jabatan'],
                'status' => $data['status'],
                'gaji_pokok' => $data['gaji_pokok'],
                'gaji_dibayar' => $data['gaji_dibayar'],
                'total_premi' => $data['total_premi'],
                'total' => $data['total'],
                'jumlah_sumber_premi' => $data['jumlah_sumber_premi'],
                'premi_breakdown' => $data['premi_breakdown'],
            ]
        );
    }

    public function replaceGajiTahap2Details(gajiTahap2Model $gaji, Collection $details): void
    {
        $gaji->details()->delete();

        if ($details->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $details
            ->map(fn (array $detail) => [
                'gaji_tahap2_id' => $gaji->id,
                'source_key' => $detail['source_key'],
                'source_label' => $detail['source_label'],
                'source_table' => $detail['source_table'],
                'source_id' => $detail['source_id'],
                'role_label' => $detail['role_label'],
                'nominal' => $detail['nominal'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
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

        $this->applyLockedSource($query, $headerTable, 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.pegawai_id', $niks)
            ->where('d.total_received', '>', 0)
            ->select([
                'd.id as source_id',
                'd.pegawai_id as nik',
                'd.role_label',
                'd.total_received as nominal',
                'h.'.$typeColumn.' as source_type',
            ])
            ->orderBy('d.pegawai_id')
            ->get()
            ->each(function ($row) use ($rows, $detailTable, $label) {
                $sourceType = strtoupper((string) ($row->source_type ?? ''));
                $roleLabel = (string) ($row->role_label ?? $label);

                $rows->push([
                    'nik' => (string) $row->nik,
                    'source_key' => str($label.' '.$sourceType)->slug('_')->toString(),
                    'source_label' => trim($label.' '.$sourceType.' - '.$roleLabel, ' -'),
                    'source_table' => $detailTable,
                    'source_id' => (int) $row->source_id,
                    'role_label' => $roleLabel,
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
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'casemix',
                'source_label' => 'Casemix - '.($row->role_label ?: 'Penerima'),
                'source_table' => 'generate_casemix_detail',
                'source_id' => (int) $row->source_id,
                'role_label' => $row->role_label ?: 'Penerima',
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
                'h.jenis_fisio',
                'h.nama_tindakan',
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
                'pegawai_id as nik',
                'total_premi_pegawai as nominal',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'driver',
                'source_label' => 'Driver',
                'source_table' => 'generate_premi_driver',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Driver',
                'nominal' => (int) round((float) $row->nominal),
            ]));
    }

    private function appendNonMedisPremium(Collection $rows, array $niks, string $periode): void
    {
        if (! $this->hasTables(['premi_pelayanan_non_medis', 'premi_pelayanan_non_medis_distribution'])) {
            return;
        }

        $query = DB::table('premi_pelayanan_non_medis_distribution as d')
            ->join('premi_pelayanan_non_medis as h', 'h.id', '=', 'd.premi_pelayanan_non_medis_id');

        $this->applyLockedSource($query, 'premi_pelayanan_non_medis', 'h')
            ->where('h.periode', $periode)
            ->whereIn('d.nik', $niks)
            ->where('d.total_diterima', '>', 0)
            ->select([
                'd.id as source_id',
                'd.nik',
                'd.total_diterima as nominal',
                'h.jenis_pelayanan',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'pelayanan_non_medis_'.strtolower((string) $row->jenis_pelayanan),
                'source_label' => 'Pelayanan Non Medis '.strtoupper((string) $row->jenis_pelayanan),
                'source_table' => 'premi_pelayanan_non_medis_distribution',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Penerima',
                'nominal' => (int) round((float) $row->nominal),
            ]));
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
                'h.jenis_pelayanan',
                'h.nama_premi',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'tindakan_medis_'.strtolower((string) $row->jenis_pelayanan),
                'source_label' => trim('Tindakan Medis '.strtoupper((string) $row->jenis_pelayanan).' - '.($row->nama_premi ?: ''), ' -'),
                'source_table' => 'generate_tindakan_medis_distribution',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Penerima',
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
                'h.jenis_pelayanan',
                'h.nama_premi',
            ])
            ->get()
            ->each(fn ($row) => $rows->push([
                'nik' => (string) $row->nik,
                'source_key' => 'premi_bersama_'.strtolower((string) $row->jenis_pelayanan),
                'source_label' => trim('Premi Bersama '.strtoupper((string) $row->jenis_pelayanan).' - '.($row->nama_premi ?: ''), ' -'),
                'source_table' => 'generate_premi_bersama_distribution',
                'source_id' => (int) $row->source_id,
                'role_label' => 'Penerima',
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
                'd.total_premi as nominal',
                'h.jenis_premi_dokter',
                'h.jenis_pelayanan',
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
