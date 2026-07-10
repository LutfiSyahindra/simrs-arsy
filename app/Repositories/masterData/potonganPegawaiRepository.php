<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\potonganPegawaiModel;

class potonganPegawaiRepository
{
    public function getPotonganPegawai()
    {
        return potonganPegawaiModel::select([
            'id',
            'nik',
            'potongan_id',
            'nominal',
        ])
            ->with(['jenisPotongan', 'gapok'])
            ->get();
    }

    public function updateOrCreate(array $condition, array $data)
    {
        return potonganPegawaiModel::updateOrCreate($condition, $data);
    }

    public function exists($nik, $potonganId)
    {
        return potonganPegawaiModel::where('nik', $nik)
            ->where('potongan_id', $potonganId)
            ->exists();
    }

    public function updateNominal($id, $nominal)
    {
        return potonganPegawaiModel::where('id', $id)
            ->update([
                'nominal' => $nominal,
                'updated_at' => now(),
            ]);
    }

    public function bulkUpdate(array $data)
    {
        foreach ($data as $item) {
            potonganPegawaiModel::where('id', $item['id'])
                ->update([
                    'nominal' => $item['nominal'],
                    'updated_at' => now(),
                ]);
        }

        return true;
    }

    public function delete($id)
    {
        return potonganPegawaiModel::where('id', $id)->delete();
    }

    public function getByPegawai($nik)
    {
        return potonganPegawaiModel::with('jenisPotongan:id,kode,nama')
            ->where('nik', $nik)
            ->get();
    }

    public function getGapokById($nik)
    {
        return gapokModel::select(
            'nik',
            'nama',
            'jbtn',
            'stts_kerja',
            'gaji_pokok',
            'mulai_kontrak'
        )
            ->where('nik', $nik)
            ->first();
    }
}
