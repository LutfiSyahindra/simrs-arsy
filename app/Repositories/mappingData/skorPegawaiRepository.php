<?php

namespace App\Repositories\mappingData;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\skorModel;
use App\Models\dbSimrs\skorPegawaiModel;

class skorPegawaiRepository
{
    protected $model;

    public function __construct(skorPegawaiModel $model)
    {
        $this->model = $model;
    }
    /**
     * Create a new class instance.
     */
    public function getSkorPegawai()
    {
        return $this->model::select('id', 'nik', 'skor_id', 'bobot_skor')
            ->with(['skor:id,kd_skor,jenis,keterangan,bobot_skor', 'gapok:nik,nama,jbtn,stts_kerja,gaji_pokok'])
            ->get();
    }

    public function getPegawai()
    {
        return gapokModel::select('nik', 'nama', 'jbtn', 'stts_kerja', 'gaji_pokok')
            ->where('stts_aktif', 'AKTIF')
            ->orderBy('nama')
            ->get();
    }

    public function getMasterSkor()
    {
        return skorModel::select('id', 'kd_skor', 'jenis', 'keterangan', 'bobot_skor')
            ->orderBy('jenis')
            ->orderBy('keterangan')
            ->get();
    }

    public function findSkorByIds(array $ids)
    {
        return skorModel::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function findSkorById(int $id)
    {
        return skorModel::find($id);
    }

    public function findById($id)
    {
        return $this->model::with('skor:id,kd_skor,jenis,keterangan,bobot_skor')
            ->find($id);
    }

    public function existingSkorIds(string $nik, array $ids)
    {
        return $this->model::where('nik', $nik)
            ->whereIn('skor_id', $ids)
            ->pluck('skor_id')
            ->toArray();
    }

    public function existsSkorForPegawai(string $nik, int $skorId, $exceptId = null)
    {
        return $this->model::where('nik', $nik)
            ->where('skor_id', $skorId)
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

        return $row->fresh('skor:id,kd_skor,jenis,keterangan,bobot_skor');
    }

    public function getByPegawai(string $nik)
    {
        return $this->model::select('id', 'nik', 'skor_id', 'bobot_skor')
            ->with('skor:id,kd_skor,jenis,keterangan,bobot_skor')
            ->where('nik', $nik)
            ->orderBy('skor_id')
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
