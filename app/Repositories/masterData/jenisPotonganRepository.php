<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\jnsPotonganModel;

class jenisPotonganRepository
{
    public function getJnsPotongan()
    {
        return jnsPotonganModel::select('id', 'kode', 'nama', 'tipe', 'nilai', 'keterangan')
            ->orderBy('kode')
            ->get();
    }

    public function getLast()
    {
        return jnsPotonganModel::orderBy('id', 'desc')->first();
    }

    public function create(array $data)
    {
        return jnsPotonganModel::insert($data);
    }

    public function findById($id)
    {
        return jnsPotonganModel::find($id);
    }

    public function update($id, array $data)
    {
        $model = jnsPotonganModel::findOrFail($id);

        $model->update([
            'nama' => $data['nama'],
            'tipe' => $data['tipe'],
            'nilai' => $data['nilai'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return $model;
    }

    public function delete($id)
    {
        $jnsPotongan = jnsPotonganModel::findOrFail($id);
        $jnsPotongan->delete();

        return true;
    }
}
