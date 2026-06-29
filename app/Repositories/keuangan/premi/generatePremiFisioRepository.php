<?php

namespace App\Repositories\keuangan\premi;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\generatePremiFisioConfigModel;
use App\Models\dbSimrs\generatePremiFisioConfigTindakanModel;
use App\Models\dbSimrs\generatePremiFisioModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class generatePremiFisioRepository
{
    public function getResults(?string $periode = null, ?string $jenisFisio = null): Collection
    {
        return generatePremiFisioModel::query()
            ->with(['lockedBy:id,name', 'generateBy:id,name'])
            ->withCount([
                'details as recipient_count' => fn ($query) => $query->where('detail_type', 'recipient'),
            ])
            ->when($periode, fn ($query) => $query->where('periode', $periode))
            ->when($jenisFisio, fn ($query) => $query->where('jenis_fisio', $jenisFisio))
            ->orderByDesc('periode')
            ->orderBy('jenis_fisio')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getSummary(?string $periode, string $jenisFisio): array
    {
        $rows = $periode
            ? generatePremiFisioModel::query()
                ->where('periode', $periode)
                ->where('jenis_fisio', $jenisFisio)
                ->get()
            : collect();

        return [
            'generated_count' => $rows->count(),
            'locked_count' => $rows->where('is_locked', true)->count(),
            'jumlah_tindakan' => $rows->sum('jumlah_tindakan'),
            'jumlah_pasien' => $rows->sum('jumlah_pasien'),
            'grand_total' => $rows->sum('grand_total'),
            'total_petugas1' => $rows->sum('total_petugas1'),
            'total_petugas2' => $rows->sum('total_petugas2'),
            'total_premi_bersama' => $rows->sum('total_premi_bersama'),
            'total_dibagikan' => $rows->sum('total_dibagikan'),
        ];
    }

    public function getConfig(string $jenisFisio): generatePremiFisioConfigModel
    {
        $this->ensureDefaultConfigs();

        return generatePremiFisioConfigModel::query()
            ->with('pegawai')
            ->where('jenis_fisio', $jenisFisio)
            ->firstOrFail();
    }

    public function getActions(bool $activeOnly = false, ?string $keyword = null): Collection
    {
        return generatePremiFisioConfigTindakanModel::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('nama_tindakan', 'like', "%{$keyword}%")
                        ->orWhere('kode_tindakan', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('nama_tindakan')
            ->limit($activeOnly ? 50 : 200)
            ->get();
    }

    public function saveConfig(
        string $jenisFisio,
        array $payload,
        array $recipients,
        ?array $actions = null
    ): generatePremiFisioConfigModel {
        $this->ensureDefaultConfigs();

        return DB::transaction(function () use ($jenisFisio, $payload, $recipients, $actions) {
            $config = generatePremiFisioConfigModel::query()
                ->where('jenis_fisio', $jenisFisio)
                ->lockForUpdate()
                ->firstOrFail();

            $config->update($payload);
            $config->pegawai()->delete();

            $now = now();
            $rows = collect($recipients)->map(fn ($item) => [
                'config_id' => $config->id,
                'role' => $item['role'],
                'pegawai_id' => $item['pegawai_id'],
                'pegawai_name' => $item['pegawai_name'],
                'pegawai_position' => $item['pegawai_position'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows) {
                DB::table('generate_premi_fisio_config_pegawai')->insert($rows);
            }

            if (is_array($actions)) {
                $this->saveActions($actions);
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

    public function findTindakan(int $id): ?generatePremiFisioConfigTindakanModel
    {
        return generatePremiFisioConfigTindakanModel::query()
            ->where('is_active', true)
            ->find($id);
    }

    public function findExistingForUpdate(
        string $periode,
        string $jenisFisio,
        string $kodeGenerate
    ): ?generatePremiFisioModel {
        return generatePremiFisioModel::query()
            ->where('periode', $periode)
            ->where('jenis_fisio', $jenisFisio)
            ->where('kode_generate', $kodeGenerate)
            ->lockForUpdate()
            ->first();
    }

    public function saveResult(array $calculation): generatePremiFisioModel
    {
        $result = generatePremiFisioModel::query()->updateOrCreate(
            [
                'periode' => $calculation['periode'],
                'jenis_fisio' => $calculation['jenis_fisio'],
                'kode_generate' => $calculation['kode_generate'],
            ],
            [
                'tindakan_config_id' => $calculation['source']['tindakan_config_id'],
                'nama_tindakan' => $calculation['source']['nama_tindakan'],
                'harga_tindakan' => $calculation['source']['harga_tindakan'],
                'jumlah_tindakan' => $calculation['source']['jumlah_tindakan'],
                'jumlah_pasien' => $calculation['source']['jumlah_pasien'],
                'grand_total' => $calculation['grand_total'],
                'total_petugas1' => $calculation['pools']['petugas1'],
                'total_petugas2' => $calculation['pools']['petugas2'],
                'total_premi_bersama' => $calculation['pools']['premi_bersama'],
                'total_dibagikan' => $calculation['total_dibagikan'],
                'config_snapshot' => $calculation['config_snapshot'] ?? $calculation['config'],
                'generate_by' => Auth::id(),
            ]
        );

        $result->details()->delete();

        $now = now();
        $baseDetail = [
            'generate_premi_fisio_id' => $result->id,
            'detail_type' => null,
            'source_label' => null,
            'tindakan_config_id' => null,
            'nama_tindakan' => null,
            'harga_tindakan' => 0,
            'jumlah' => 0,
            'subtotal' => 0,
            'role' => null,
            'role_label' => null,
            'pegawai_id' => null,
            'pegawai_name' => null,
            'pegawai_position' => null,
            'allocation_percent' => null,
            'basis_amount' => 0,
            'total_received' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $sourceRows = collect($calculation['source_details'])->map(fn ($item) => [
            ...$baseDetail,
            'detail_type' => 'source',
            'source_label' => $item['source_label'],
            'tindakan_config_id' => $item['tindakan_config_id'],
            'nama_tindakan' => $item['nama_tindakan'],
            'harga_tindakan' => $item['harga_tindakan'],
            'jumlah' => $item['jumlah'],
            'subtotal' => $item['subtotal'],
        ]);
        $recipientRows = collect($calculation['recipients'])->map(fn ($item) => [
            ...$baseDetail,
            'detail_type' => 'recipient',
            'role' => $item['role'],
            'role_label' => $item['role_label'],
            'pegawai_id' => $item['pegawai_id'],
            'pegawai_name' => $item['pegawai_name'],
            'pegawai_position' => $item['pegawai_position'],
            'allocation_percent' => $item['allocation_percent'],
            'basis_amount' => $item['basis_amount'],
            'total_received' => $item['total_received'],
        ]);

        $details = $sourceRows->merge($recipientRows)->all();

        if ($details) {
            DB::table('generate_premi_fisio_detail')->insert($details);
        }

        return $result->fresh(['details', 'lockedBy:id,name', 'generateBy:id,name']);
    }

    public function findForUpdate(int $id): ?generatePremiFisioModel
    {
        return generatePremiFisioModel::query()
            ->lockForUpdate()
            ->find($id);
    }

    public function findWithDetails(int $id): ?generatePremiFisioModel
    {
        return generatePremiFisioModel::query()
            ->with([
                'details' => fn ($query) => $query
                    ->orderBy('detail_type')
                    ->orderBy('role')
                    ->orderBy('pegawai_name'),
                'lockedBy:id,name',
                'generateBy:id,name',
            ])
            ->find($id);
    }

    public function updateLock(
        generatePremiFisioModel $result,
        bool $isLocked,
        ?int $userId = null
    ): generatePremiFisioModel {
        DB::table('generate_premi_fisio')
            ->where('id', $result->id)
            ->update([
                'is_locked' => $isLocked,
                'locked_at' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? $userId : null,
            ]);

        return generatePremiFisioModel::query()
            ->with('lockedBy:id,name')
            ->findOrFail($result->id);
    }

    public function deleteResult(generatePremiFisioModel $result): void
    {
        $result->delete();
    }

    private function saveActions(array $actions): void
    {
        $savedIds = [];
        $sort = 1;

        foreach ($actions as $item) {
            $name = trim((string) ($item['nama_tindakan'] ?? ''));

            if ($name === '') {
                continue;
            }

            $payload = [
                'kode_tindakan' => trim((string) ($item['kode_tindakan'] ?? '')) ?: null,
                'nama_tindakan' => $name,
                'harga' => max(0, (int) ($item['harga'] ?? 0)),
                'is_active' => (bool) ($item['is_active'] ?? true),
                'sort_order' => $sort++,
                'note' => trim((string) ($item['note'] ?? '')) ?: null,
            ];

            $id = (int) ($item['id'] ?? 0);
            $row = $id ? generatePremiFisioConfigTindakanModel::query()->find($id) : null;

            if ($row) {
                $row->update($payload);
            } else {
                $row = generatePremiFisioConfigTindakanModel::query()->create($payload);
            }

            $savedIds[] = $row->id;
        }

        generatePremiFisioConfigTindakanModel::query()
            ->when($savedIds, fn ($query) => $query->whereNotIn('id', $savedIds))
            ->update(['is_active' => false]);
    }

    private function ensureDefaultConfigs(): void
    {
        generatePremiFisioConfigModel::query()->firstOrCreate(
            ['jenis_fisio' => 'umum'],
            [
                'grand_mode' => 'tindakan',
                'grand_nominal' => 0,
                'petugas1_mode' => 'percent',
                'petugas1_percent' => 50,
                'petugas1_nominal' => 0,
                'petugas2_mode' => 'percent',
                'petugas2_percent' => 50,
                'petugas2_nominal' => 0,
                'bersama_mode' => 'percent',
                'bersama_percent' => 50,
                'bersama_nominal' => 0,
            ]
        );

        generatePremiFisioConfigModel::query()->firstOrCreate(
            ['jenis_fisio' => 'bpjs'],
            [
                'grand_mode' => 'patient_nominal',
                'grand_nominal' => 8000,
                'petugas1_mode' => 'nominal',
                'petugas1_percent' => 50,
                'petugas1_nominal' => 4000,
                'petugas2_mode' => 'percent',
                'petugas2_percent' => 50,
                'petugas2_nominal' => 0,
                'bersama_mode' => 'percent',
                'bersama_percent' => 50,
                'bersama_nominal' => 0,
            ]
        );
    }
}
