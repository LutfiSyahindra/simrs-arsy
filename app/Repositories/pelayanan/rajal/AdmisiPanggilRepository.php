<?php

namespace App\Repositories\pelayanan\rajal;

use App\Models\dbSimrs\admisiModel;
use Carbon\Carbon;

class AdmisiPanggilRepository
{
    /**
     * Create a new class instance.
     */

    public function getAllAdmisi()
    {
        return admisiModel::where('tanggal', Carbon::now()->format('Y-m-d'))
        ->orderBy('no_antrian')
        ->get(['id', 'no_antrian', 'status_panggil', 'loket']);
    }

    public function updateStatusPanggil($id)
    {
        admisiModel::where('id', $id)
        ->update(['status_panggil' => 'Sudah']);
    }

    public function updateLoket($id, $loket)
    {
        admisiModel::where('id', $id)
        ->update(['loket' => $loket]);
    }


}
