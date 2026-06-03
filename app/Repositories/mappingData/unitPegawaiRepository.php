<?php

namespace App\Repositories\mappingData;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\unitModel;
use App\Models\dbSimrs\unitPegawaiModel;

class unitPegawaiRepository
{
    protected $model;

    public function __construct(unitPegawaiModel $model)
    {
        $this->model = $model;
    }
    /**
     * Create a new class instance.
     */
    public function getUnitPegawai()
    {
        return $this->model::select('id', 'nik', 'unit_id')
            ->with(['unit:id,kode,jenis,keterangan', 'gapok:nik,nama,jbtn,stts_kerja,gaji_pokok'])
            ->get();
    }

    public function getPegawai()
    {
        return gapokModel::select('nik', 'nama', 'jbtn', 'stts_kerja', 'gaji_pokok')
            ->where('stts_aktif', 'AKTIF')
            ->orderBy('nama')
            ->get();
    }

    public function getMasterUnit()
    {
        return unitModel::select('id', 'kode', 'jenis', 'keterangan')
            ->orderBy('jenis')
            ->orderBy('keterangan')
            ->get();
    }

    public function findUnitByIds(array $ids)
    {
        return unitModel::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function findUnitById(int $id)
    {
        return unitModel::find($id);
    }

    public function findById($id)
    {
        return $this->model::with('unit:id,kode,jenis,keterangan')
            ->find($id);
    }

    public function existingUnitIds(string $nik, array $ids)
    {
        return $this->model::where('nik', $nik)
            ->whereIn('unit_id', $ids)
            ->pluck('unit_id')
            ->toArray();
    }

    public function existingNiksForUnit(int $unitId, array $niks)
    {
        return $this->model::where('unit_id', $unitId)
            ->whereIn('nik', $niks)
            ->pluck('nik')
            ->toArray();
    }

    public function existsUnitForPegawai(string $nik, int $unitId, $exceptId = null)
    {
        return $this->model::where('nik', $nik)
            ->where('unit_id', $unitId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    public function insert(array $data)
    {
        return $this->model::insert($data);
    }

    public function update($id, array $data)
    {
        $row = $this->model::findOrFail($id);
        $row->update($data);

        return $row->fresh('unit:id,kode,jenis,keterangan');
    }

    public function getByPegawai(string $nik)
    {
        return $this->model::select('id', 'nik', 'unit_id')
            ->with('unit:id,kode,jenis,keterangan')
            ->where('nik', $nik)
            ->orderBy('unit_id')
            ->get();
    }

    public function getGapokById(string $nik)
    {
        return gapokModel::select('nik', 'nama', 'jbtn', 'stts_kerja', 'gaji_pokok')
            ->where('nik', $nik)
            ->first();
    }

    public function delete($id)
    {
        return $this->model::where('id', $id)->delete();
    }
}
