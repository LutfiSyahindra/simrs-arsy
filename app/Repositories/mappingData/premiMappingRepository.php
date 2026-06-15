<?php

namespace App\Repositories\mappingData;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsPremiModel;
use App\Models\dbSimrs\jnsTindakanModel;
use App\Models\dbSimrs\mappingPremiModel;
use App\Models\dbSimrs\mappingPremiPegawaiModel;
use Illuminate\Support\Facades\DB;

class premiMappingRepository
{
    protected $mappingPremiModel;

    protected $mappingPremiPegawaiModel;

    protected $masterJnsPremiModel;

    protected $masterJnsTindakanModel;

    protected $gapokModel;

    public function __construct(
        mappingPremiModel $mappingPremiModel,
        mappingPremiPegawaiModel $mappingPremiPegawaiModel,
        jnsPremiModel $masterJnsPremiModel,
        jnsTindakanModel $masterJnsTindakanModel,
        gapokModel $gapokModel
    ) {
        $this->mappingPremiModel = $mappingPremiModel;
        $this->mappingPremiPegawaiModel = $mappingPremiPegawaiModel;
        $this->masterJnsPremiModel = $masterJnsPremiModel;
        $this->masterJnsTindakanModel = $masterJnsTindakanModel;
        $this->gapokModel = $gapokModel;
    }

    public function getPremiWithCounts()
    {
        return $this->masterJnsPremiModel::select('id', 'kode', 'jenis')
            ->withCount([
                'mappingPremi as jumlah_tindakan',
                'pegawaiPremi as jumlah_pegawai',
            ])
            ->orderBy('jenis')
            ->get();
    }

    public function getMasterJnsPremi()
    {
        return $this->masterJnsPremiModel::select('id', 'kode', 'jenis')
            ->orderBy('jenis')
            ->get();
    }

    public function getMasterJnsTindakan()
    {
        return $this->masterJnsTindakanModel::select('id', 'kode', 'jenis')
            ->orderBy('jenis')
            ->get();
    }

    public function getPegawai()
    {
        return $this->gapokModel::select('nik', 'nama', 'jbtn', 'stts_kerja')
            ->where('stts_aktif', 'AKTIF')
            ->orderBy('nama')
            ->get();
    }

    public function getPegawaiByPremi(int $premiId)
    {
        return $this->mappingPremiPegawaiModel::select('id', 'jnsPremi_id', 'nik')
            ->with('gapok:nik,nama,jbtn,stts_kerja')
            ->where('jnsPremi_id', $premiId)
            ->get();
    }

    public function syncPegawai(int $premiId, array $niks)
    {
        return DB::transaction(function () use ($premiId, $niks) {
            $this->mappingPremiPegawaiModel::where('jnsPremi_id', $premiId)->delete();

            if (empty($niks)) {
                return true;
            }

            $now = now();
            $data = collect($niks)->map(fn ($nik) => [
                'jnsPremi_id' => $premiId,
                'nik' => $nik,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            return $this->mappingPremiPegawaiModel::insert($data);
        });
    }

    public function findPremiById(int $id)
    {
        return $this->masterJnsPremiModel::select('id', 'kode', 'jenis')->find($id);
    }

    public function findTindakanByIds(array $ids)
    {
        return $this->masterJnsTindakanModel::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function getMappingsByPremi(int $premiId)
    {
        return $this->mappingPremiModel::select(
            'id',
            'jnsPremi_id',
            'jnsTindakan_id',
            'nilai',
            'jenis',
            'nilai_umum',
            'nilai_bpjs'
        )
            ->with('jnsTindakan:id,kode,jenis')
            ->where('jnsPremi_id', $premiId)
            ->orderBy('jnsTindakan_id')
            ->get();
    }

    public function findById($id)
    {
        return $this->mappingPremiModel::with([
            'jnsPremi:id,kode,jenis',
            'jnsTindakan:id,kode,jenis',
        ])->find($id);
    }

    public function existingTindakanIds(int $premiId, array $tindakanIds)
    {
        return $this->mappingPremiModel::where('jnsPremi_id', $premiId)
            ->whereIn('jnsTindakan_id', $tindakanIds)
            ->pluck('jnsTindakan_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function existsMapping(int $premiId, int $tindakanId, $exceptId = null)
    {
        return $this->mappingPremiModel::where('jnsPremi_id', $premiId)
            ->where('jnsTindakan_id', $tindakanId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    public function insert(array $data)
    {
        return $this->mappingPremiModel::insert($data);
    }

    public function update($id, array $data)
    {
        $row = $this->mappingPremiModel::findOrFail($id);
        $row->update($data);

        return $row->fresh([
            'jnsPremi:id,kode,jenis',
            'jnsTindakan:id,kode,jenis',
        ]);
    }

    public function delete($id)
    {
        return $this->mappingPremiModel::where('id', $id)->delete();
    }
}
