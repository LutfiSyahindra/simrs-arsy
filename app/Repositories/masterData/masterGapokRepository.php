<?php

namespace App\Repositories\masterData;

use App\Models\dbSimrs\gapokModel;

class masterGapokRepository
{
    /**
     * Create a new class instance.
     */

    public function getGapok()
    {
        return gapokModel::select('id','nik','nama','jbtn','stts_kerja','masa_kerja','gaji_pokok')->get();
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
