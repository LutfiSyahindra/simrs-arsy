<?php

namespace App\Repositories\pelayanan\rajal;

use App\Models\dbKhanza\dokterModel;
use App\Models\dbKhanza\pasienPoliModel;
use App\Models\dbKhanza\poliModel;
use Carbon\Carbon;

class PoliRepository
{
    public function getAllPoli()
    {
        return poliModel::getPoli();
    
    }
    public function getDokter($kd_poli)
    {
        return dokterModel::getDokter($kd_poli);
    }

    public function getPasien($kd_poli, $kd_sps)
    {
        return pasienPoliModel::getPasien($kd_poli, $kd_sps);
    }

    public function getDataPasien()
    {
        return pasienPoliModel::join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
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
        ->whereDate('reg_periksa.tgl_registrasi', Carbon::today())
        ->cursor();
    }

    public function getDataPasienRanap($tgl1, $tgl2)
    {
        return pasienPoliModel::join('kamar_inap', 'reg_periksa.no_rawat', '=', 'kamar_inap.no_rawat')
        ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
        ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
        ->select(
            'reg_periksa.no_rawat',
            'reg_periksa.no_reg',
            'pasien.nm_pasien',
            'pasien.tgl_lahir',
            'pasien.alamat',
            'pasien.jk',
            'pasien.no_rkm_medis',
            'dokter.nm_dokter'
        )
        ->whereBetween('kamar_inap.tgl_masuk', [$tgl1, $tgl2])
        ->cursor();
    }

    public function getDataPasienIgd()
    {
        return pasienPoliModel::join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
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
        ->where('reg_periksa.kd_poli', 'IGDK') // 🔴 khusus IGD
        ->whereDate('reg_periksa.tgl_registrasi', Carbon::today())
        ->cursor();
    }

}
