<?php

namespace App\Services\keuangan\penggajian;

use App\Repositories\keuangan\penggajian\penggajianRepository;
use Illuminate\Support\Facades\DB;

class penggajianService
{
    protected $penggajianRepository;
    public function __construct()
    {
        $this->penggajianRepository = new penggajianRepository();
    }

    public function getSummaryGajiTahap1(?string $periode)
    {
        $data = $this->penggajianRepository->getGajiTahap1Table($periode);

        return [
            'jumlah_pegawai' => $data->count(),
            'jumlah_tetap' => $data->where('status', 'T')->count(),
            'jumlah_kontrak' => $data->where('status', 'FT')->count(),
            'total_gaji' => $data->sum(function ($row) {
                return (int) $row->gaji_dibayar + (int) $row->tunjangan;
            }),
            'total_gapok' => $data->sum('gaji_dibayar'),
            'total_tunjangan' => $data->sum('tunjangan'),
            'periode' => $periode,
        ];
    }

    public function generateGajiTahap1(string $periode)
    {
        return DB::transaction(function () use ($periode) {
            $pegawaiList = $this->penggajianRepository->getPegawaiUntukGajiTahap1();

            $jumlahPegawai = 0;
            $jumlahTetap = 0;
            $jumlahKontrak = 0;
            $totalGaji = 0;

            foreach ($pegawaiList as $pegawai) {
                $gajiPokok = (int) ($pegawai->nominal_gaji_pokok ?? 0);
                $tunjangan = (int) ($pegawai->nominal_tunjangan ?? 0);
                $status = strtoupper(trim($pegawai->status ?? ''));

                if ($status === 'T') {
                    $gajiDibayar = $gajiPokok;
                    $tunjanganDibayar = $tunjangan;
                    $jumlahTetap++;
                } elseif ($status === 'FT') {
                    $gajiDibayar = (int) round($gajiPokok * 0.5);
                    $tunjanganDibayar = 0;
                    $jumlahKontrak++;
                } else {
                    $gajiDibayar = 0;
                    $tunjanganDibayar = 0;
                }

                $this->penggajianRepository->updateOrCreateGajiTahap1([
                    'periode' => $periode,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                    'jabatan' => $pegawai->jbtn,
                    'status' => $pegawai->status,
                    'gaji_pokok' => $gajiPokok,
                    'gaji_dibayar' => $gajiDibayar,
                    'tunjangan' => $tunjanganDibayar,
                ]);

                $jumlahPegawai++;
                $totalGaji += $gajiDibayar + $tunjanganDibayar;
            }

            return [
                'jumlah_pegawai' => $jumlahPegawai,
                'jumlah_tetap' => $jumlahTetap,
                'jumlah_kontrak' => $jumlahKontrak,
                'total_gaji' => $totalGaji,
            ];
        });
    }

    public function getGajiTahap1Table($periode)
    {
        $gajiTahap1Table = $this->penggajianRepository->getGajiTahap1Table($periode);

        $dataGajiTahap1Table = [];
        foreach ($gajiTahap1Table as $gajiTahap1) {
            $gajiDibayarkan = (int) $gajiTahap1->gaji_dibayar;
            $tunjangan = (int) $gajiTahap1->tunjangan;

            $dataGajiTahap1Table[] = [
                'id' => $gajiTahap1->id,
                'nik' => $gajiTahap1->nik,
                'nama_pegawai' => $gajiTahap1->nama,
                'jabatan' => $gajiTahap1->jabatan,
                'status' => $gajiTahap1->status,
                'gapok' => (int) $gajiTahap1->gaji_pokok,
                'gaji_dibayarkan' => $gajiDibayarkan,
                'tunjangan' => $tunjangan,
                'total' => $gajiDibayarkan + $tunjangan,
                'periode' => $gajiTahap1->periode,
            ];
        }

        return collect($dataGajiTahap1Table);
    }

    public function detailGajiTahap1($id)
    {
        $gaji = $this->penggajianRepository->findGajiTahap1ById($id);

        if (!$gaji) {
            abort(404, 'Data gaji tahap 1 tidak ditemukan');
        }

        $tunjanganList = $this->penggajianRepository
        ->getTunjanganPegawai($gaji->nik)
        ->map(function ($tunjangan) use ($gaji) {
            return [
                'nama' => $tunjangan->nama_tunjangan ?? 'Tunjangan',
                'nominal' => $gaji->status === 'T' ? (int) $tunjangan->nominal : 0,
            ];
        })
        ->values();

        $gajiDibayar = (int) $gaji->gaji_dibayar;
        $tunjangan = (int) $gaji->tunjangan;

        return [
            'id' => $gaji->id,
            'periode' => $gaji->periode,
            'nik' => $gaji->nik,
            'nama' => $gaji->nama,
            'jabatan' => $gaji->jabatan,
            'status' => $gaji->status,
            'status_label' => $gaji->status === 'T' ? 'Pegawai Tetap' : 'Pegawai Kontrak',
            'gaji_pokok' => (int) $gaji->gaji_pokok,
            'gaji_dibayar' => $gajiDibayar,
            'tunjangan' => $tunjangan,
            'tunjangan_detail' => $tunjanganList,
            'total' => $gajiDibayar + $tunjangan,
        ];
    }

}
