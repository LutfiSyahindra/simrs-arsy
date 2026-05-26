<?php

namespace App\Repositories\keuangan\penggajian;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gajiTahap1Model;
use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\tunjanganPegawaiModel;
use Illuminate\Support\Facades\Log;

class penggajianRepository
{

        public function getTunjanganPegawai($nik)
    {
        return tunjanganPegawaiModel::query()
            ->leftJoin('master_tunjangan', 'master_tunjangan.id', '=', 'tunjangan_pegawai.tunjangan_id')
            ->where('tunjangan_pegawai.nik', $nik)
            ->select([
                'tunjangan_pegawai.nominal',
                'master_tunjangan.nama as nama_tunjangan',
            ])
            ->get();
    }

    public function getGajiTahap1Table($periode)
    {
        return gajiTahap1Model::select('id', 'nik', 'nama', 'jabatan', 'status', 'gaji_pokok', 'gaji_dibayar', 'tunjangan', 'periode')->where('periode', $periode)->get();
    }

    public function getPegawaiUntukGajiTahap1()
    {
        $pegawaiList = pegawaiModel::query()
            ->select([
                'nik',
                'nama',
                'jbtn',
                'stts_kerja',
            ])
            ->where('stts_aktif', 'AKTIF')
            ->get();

        $gajiPokokList = gapokModel::query()
            ->pluck('gaji_pokok', 'nik');

        $tunjanganList = tunjanganPegawaiModel::query()
            ->selectRaw('nik, SUM(nominal) as total_tunjangan')
            ->groupBy('nik')
            ->pluck('total_tunjangan', 'nik');

        return $pegawaiList->map(function ($pegawai) use ($gajiPokokList, $tunjanganList) {
            $pegawai->status = $pegawai->stts_kerja;
            $pegawai->nominal_gaji_pokok = $gajiPokokList[$pegawai->nik] ?? 0;
            $pegawai->nominal_tunjangan = $tunjanganList[$pegawai->nik] ?? 0;

            return $pegawai;
        });
    }
    public function updateOrCreateGajiTahap1(array $data)
    {
        Log::info('Data untuk updateOrCreateGajiTahap1:', $data);
        return gajiTahap1Model::updateOrCreate(
            [
                'periode' => $data['periode'],
                'nik' => $data['nik'],
            ],
            [
                'nama' => $data['nama'],
                'jabatan' => $data['jabatan'],
                'status' => $data['status'],
                'gaji_pokok' => $data['gaji_pokok'],
                'gaji_dibayar' => $data['gaji_dibayar'],
                'tunjangan' => $data['tunjangan'],
            ]
        );
    }

    public function findGajiTahap1ById($id)
    {
        return gajiTahap1Model::find($id);
    }
}
