<?php

namespace App\Repositories\pelayanan\rajal;

use App\Models\dbSimrs\loketModel;

class LoketAdmisiRepository
{
    /**
     * Create a new class instance.
     */
    public function getAllLoket()
    {
        return loketModel::where('sedang_dipakai', 0)->get();
    }

    public function lockLoket($id, $petugas = null)
    {
        $loket = loketModel::find($id);

        if (!$loket) {
            return [
                'status' => 'error',
                'message' => 'Loket tidak ditemukan'
            ];
        }

        if ($loket->sedang_dipakai) {
            return [
                'status' => 'error',
                'message' => "Loket {$loket->nama} sudah digunakan oleh petugas lain"
            ];
        }

        $loket->update([
            'sedang_dipakai' => true,
            'petugas' => $petugas
        ]);

        return [
            'status' => 'success',
            'message' => "Loket {$loket->nama} berhasil diaktifkan",
            'data' => $loket
        ];
    }

    public function unlockLoket($id)
    {
        $loket = LoketModel::find($id);

        if (!$loket) {
            return [
                'status' => 'error',
                'message' => 'Loket tidak ditemukan'
            ];
        }

        $loket->update([
            'sedang_dipakai' => false,
            'petugas' => null
        ]);

        return [
            'status' => 'success',
            'message' => "Loket {$loket->nama} berhasil ditutup"
        ];
    }


}
