<?php

namespace App\Repositories\masterData;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use Illuminate\Support\Facades\Schema;

class masterGapokRepository
{
    /**
     * Create a new class instance.
     */
    public function getGapok()
    {
        $select = ['id', 'nik', 'nama', 'jbtn', 'stts_kerja', 'masa_kerja', 'mulai_kontrak', 'gaji_pokok', 'no_telp'];

        if (Schema::hasColumn('gaji_pokok', 'email')) {
            $select[] = 'email';
        }

        return gapokModel::select($select)->where('stts_aktif', 'AKTIF')->get();
    }

    public function getPegawai()
    {
        return pegawaiModel::select('nik', 'nama', 'jbtn', 'stts_kerja', 'mulai_kontrak')->get();
    }

    public function createGapok($data)
    {
        return gapokModel::create($data);
    }

    public function findById($id)
    {
        return gapokModel::find($id);
    }

    public function update($id, $data)
    {
        $gapok = gapokModel::findOrFail($id);

        $gapok->update($data);

        return $gapok;
    }

    public function delete($id)
    {
        $gapok = gapokModel::findOrFail($id);

        $gapok->delete();

        return true;
    }
}
