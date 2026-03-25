<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\tunjanganPegawaiModel;

class tunjanganPegawaiRepository
{
    /**
     * Create a new class instance.
     */

    public function getTunjanganPegawai()
    {
        return tunjanganPegawaiModel::select([
                'id',
                'nik',
                'tunjangan_id',
                'nominal'
            ])
            ->with([
                'jenisTunjangan:id,kode,nama',
                'gapok:nik,nama,jbtn,stts_kerja,masa_kerja'
            ])
            ->get();
    }

    public function updateOrCreate(array $condition, array $data)
    {
        return tunjanganPegawaiModel::updateOrCreate($condition, $data);
    }

    public function exists($nik, $tunjanganId)
    {
        return tunjanganPegawaiModel::where('nik', $nik)
            ->where('tunjangan_id', $tunjanganId)
            ->exists();
    }

    public function updateNominal($id, $nominal)
    {
        return tunjanganPegawaiModel::where('id', $id)
            ->update([
                'nominal' => $nominal,
                'updated_at' => now()
            ]);
    }

    public function bulkUpdate(array $data)
    {
        foreach ($data as $item) {

            tunjanganPegawaiModel::where('id', $item['id'])
                ->update([
                    'nominal' => $item['nominal'],
                    'updated_at' => now()
                ]);
        }

        return true;
    }

    public function delete($id)
    {
        return tunjanganPegawaiModel::where('id', $id)->delete();
    }

    public function getByPegawai($nik)
    {
        return tunjanganPegawaiModel::with('jenisTunjangan:id,kode,nama')
            ->where('nik', $nik)
            ->get();
    }

    public function insertBulk(array $data)
    {
        return tunjanganPegawaiModel::insert($data);
    }
}
