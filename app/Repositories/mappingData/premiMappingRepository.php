<?php

namespace App\Repositories\mappingData;

use App\Models\dbSimrs\jnsPremiModel;
use App\Models\dbSimrs\jnsTindakanModel;
use App\Models\dbSimrs\mappingPremiModel;

class premiMappingRepository
{
    protected $mappingPremiModel;
    protected $masterJnsPremiModel;
    protected $masterJnsTindakanModel;

    public function __construct(
        mappingPremiModel $mappingPremiModel,
        jnsPremiModel $masterJnsPremiModel,
        jnsTindakanModel $masterJnsTindakanModel
    ) {
        $this->mappingPremiModel = $mappingPremiModel;
        $this->masterJnsPremiModel = $masterJnsPremiModel;
        $this->masterJnsTindakanModel = $masterJnsTindakanModel;
    }

    public function getPremiWithCounts()
    {
        return $this->masterJnsPremiModel::select('id', 'kode', 'jenis')
            ->withCount(['mappingPremi as jumlah_tindakan'])
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
        return $this->mappingPremiModel::select('id', 'jnsPremi_id', 'jnsTindakan_id', 'nilai', 'jenis')
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
