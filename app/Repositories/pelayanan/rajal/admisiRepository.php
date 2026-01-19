<?php

namespace App\Repositories\pelayanan\rajal;

use App\Models\dbSimrs\admisiModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdmisiRepository
{
    public function generateNomorAntrian()
    {
        $tanggalHariIni = Carbon::today()->toDateString();

        // Ambil nomor antrian terakhir untuk tanggal hari ini
        $lastAntrian = admisiModel::whereDate('tanggal', $tanggalHariIni)
            ->orderBy('no_antrian', 'desc')
            ->first(); // Ambil satu data terbaru

        if ($lastAntrian) {
            // Jika sudah ada antrian hari ini, tambahkan 1
            $nomorAntrian = $lastAntrian->no_antrian + 1;
        } else {
            // Jika belum ada, mulai dari 1
            $nomorAntrian = 1;
        }

        // Simpan nomor antrian baru ke database
        $antrian = admisiModel::create([
            'no_antrian' => $nomorAntrian,
            'tanggal' => $tanggalHariIni,
            'status_panggil' => 'Belum',
            'loket' => null,
        ]);

        return $antrian;
    }

}
