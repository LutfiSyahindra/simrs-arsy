<?php

namespace App\Repositories\pelayanan\rajal;

use App\Models\dbKhanza\antriapotek3Model;
use App\Models\dbKhanza\resepObatModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AntrianApotekRepository
{
    public static function getAntriapotek3()
    {
        return antriapotek3Model::selectRaw('antriapotek3.no_resep as no_reg')
            ->addSelect([
                'antriapotek3.panggil',  // Gunakan prefix tabel
                'antriapotek3.no_rawat', // Gunakan prefix tabel
                DB::raw("RIGHT(antriapotek3.no_resep, 3) AS no_resep"),
                'pasien.nm_pasien',
                'poliklinik.nm_poli',
                'dokter.nm_dokter',
            ])
            ->join('reg_periksa', 'antriapotek3.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
            ->where('antriapotek3.panggil', 'Dipanggil') // Gunakan prefix tabel
            ->limit(1)
            ->get()
            ->toArray();
    }

    public static function updateAntriapotek3($panggil){
        return antriapotek3Model::where('panggil', $panggil)
            ->update([
                'panggil' => 'Selesai'
            ]);
    }

    public static function nonRacikan()
    {
        $today = Carbon::now()->toDateString();

        return resepObatModel::select(
            DB::raw("RIGHT(resep_obat.no_resep, 3) AS no_resep"),
            'resep_obat.no_rawat', 
            'resep_obat.tgl_perawatan',
            'pasien.nm_pasien',
            DB::raw("CASE 
                        WHEN resep_obat.tgl_penyerahan != '0000-00-00' 
                            AND resep_obat.jam_penyerahan != '00:00:00' 
                        THEN 'Sudah Selesai' 
                        ELSE 'Belum Selesai' 
                    END AS status"),
            // DB::raw("DATE_ADD(resep_obat.jam, INTERVAL 10 MINUTE) AS estimasi_dilayani")
            DB::raw("CASE 
                    WHEN resep_obat.jam = '00:00:00' 
                        THEN 'Menunggu Validasi'
                    ELSE DATE_FORMAT(DATE_ADD(resep_obat.jam, INTERVAL 10 MINUTE), '%H:%i:%s')
                    END AS estimasi_dilayani")
        )
        ->leftJoin('resep_dokter_racikan', 'resep_obat.no_resep', '=', 'resep_dokter_racikan.no_resep')
        ->join('reg_periksa', 'resep_obat.no_rawat', '=', 'reg_periksa.no_rawat')
        ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
        ->leftJoin('konseling_farmasi', 'resep_obat.no_rawat', '=', 'konseling_farmasi.no_rawat')
        ->whereNull('resep_dokter_racikan.no_resep')
        ->whereDate('resep_obat.tgl_peresepan', $today)
        ->where('resep_obat.status', 'ralan')
        ->where(function ($query) {
            $query->where('resep_obat.tgl_penyerahan','0000-00-00')
                ->orWhere('resep_obat.jam_penyerahan', '00:00:00');
        })
        ->get();
    }

    public static function racikan()
    {
        $today = Carbon::now()->toDateString();

        return resepObatModel::select(
            DB::raw("RIGHT(resep_obat.no_resep, 3) AS no_resep"),
            'resep_obat.no_rawat', 
            'resep_obat.tgl_perawatan',
            'pasien.nm_pasien',
            DB::raw("CASE 
                        WHEN resep_obat.tgl_penyerahan != '0000-00-00' 
                            AND resep_obat.jam_penyerahan != '00:00:00' 
                        THEN 'Sudah Selesai' 
                        ELSE 'Belum Selesai' 
                    END AS status"),
            DB::raw("DATE_ADD(resep_obat.jam, INTERVAL 30 MINUTE) AS estimasi_dilayani")
        )
        ->leftJoin('resep_dokter_racikan', 'resep_obat.no_resep', '=', 'resep_dokter_racikan.no_resep')
        ->join('reg_periksa', 'resep_obat.no_rawat', '=', 'reg_periksa.no_rawat')
        ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
        ->leftJoin('konseling_farmasi', 'resep_obat.no_rawat', '=', 'konseling_farmasi.no_rawat')
        ->whereNotNull('resep_dokter_racikan.no_resep')
        ->whereDate('resep_obat.tgl_peresepan', $today)
        ->where('resep_obat.status', 'ralan')
        ->where(function ($query) {
            $query->where('resep_obat.tgl_penyerahan','0000-00-00')
                ->orWhere('resep_obat.jam_penyerahan', '00:00:00');
        })
        ->get();
    }




}
