<?php

namespace App\Models\dbKhanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class pasienPoliModel extends Model
{
    protected $connection = 'mysql_khanza';
    protected $table = 'reg_periksa';

    public static function getPasien($kd_poli, $kd_sps)
    {
        if (!$kd_poli && !$kd_sps) {
            return collect(); // Koleksi kosong jika tidak ada data
        }

        return DB::connection('mysql_khanza')
            ->table('reg_periksa')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_reg',
                'pasien.nm_pasien',
                'poliklinik.nm_poli',
                'pasien.tgl_lahir',
                'pasien.alamat',
                'pasien.jk',
                'pasien.no_rkm_medis',
                'dokter.nm_dokter'
            )
            ->where('reg_periksa.kd_poli', $kd_poli)
            ->where('reg_periksa.kd_dokter', $kd_sps)
            ->whereDate('reg_periksa.tgl_registrasi', Carbon::today())
            ->cursor(); // Menggunakan cursor
    }

}
