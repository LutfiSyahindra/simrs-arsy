<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\gapokExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Repositories\masterData\masterGapokRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class masterGapokService
{
    protected $masterGapokRepository;

    public function __construct(masterGapokRepository $masterGapokRepository)
    {
        $this->masterGapokRepository = $masterGapokRepository;
    }

    
    public $added = 0;
    public $updated = 0;
    public $skipped = 0;

    private function getStatusAlias($status)
    {
        return match ($status) {
            'T' => 'Tetap',
            'FT' => 'Kontrak',
            'PT' => 'Casual',
            default => '-'
        };
    }

    private function getMasaKerjaAlias($masaKerja)
    {
        return match ($masaKerja) {
            'FT>1' => 'Kontrak > 1 Tahun',
            'PT' => 'Pegawai Tetap',
            '<1' => '< 1 Tahun',
            default => $masaKerja . ' Tahun'
        };
    }

    public function getPegawai()
    {
        return $this->masterGapokRepository->getPegawai();
    }

    public function getPegawaiByNik($nik)
    {
        $pegawai = pegawaiModel::where('nik', $nik)->first();

        if (!$pegawai) {
            return null;
        }

        return [
            'nama' => $pegawai->nama,
            'jbtn' => $pegawai->jbtn,
            'stts_kerja' => $pegawai->stts_kerja,
            'mulai_kontrak' => $pegawai->mulai_kontrak ?? '-',
        ];
    }

    public function getGapokTable()
    {
        $gapok = $this->masterGapokRepository->getGapok();

        $dataGapok = [];
        if ($gapok) {
            foreach ($gapok as $g) {
                $dataGapok[] = [
                    'id' => $g->id,
                    'kode' => $g->nik,
                    'nama' => $g->nama,
                    'jabatan' => $g->jbtn,
                    'status' => $this->getStatusAlias($g->stts_kerja),
                    'masaKerja' => $this->getMasaKerjaAlias($g->masa_kerja),
                    'mulaiKontrak' => $g->mulai_kontrak ? Carbon::parse($g->mulai_kontrak)->format('d-m-Y') : '-',
                    'gaji_pokok' => (int) $g->gaji_pokok,
                ];
            }
        }

        return collect($dataGapok);
    }

    public function exportTemplate()
    {
        try {
            $fileName = 'Template_MasterGapok_Gapok.xlsx';

            // Bisa langsung dikembalikan ke controller
            return Excel::download(new gapokExport, $fileName);
        } catch (Exception $e) {
            Log::error('Gagal export template MasterGapok Gapok: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Gagal membuat template: ' . $e->getMessage(),
            ];
        }
    }

    public function resetCounter()
    {
        $this->added = 0;
        $this->updated = 0; // 🔥 tambahkan ini
        $this->skipped = 0;
    }

    // 🔥 FUNCTION HITUNG MASA KERJA
    private function hitungMasaKerja($mulaiKontrak)
    {
        if (!$mulaiKontrak) return null;

        $mulai = Carbon::parse($mulaiKontrak);
        $sekarang = Carbon::now();

        return $mulai->diff($sekarang)->format('%y Tahun %m Bulan');
    }

    public function prosesImportGapok($data)
    {
        // 🔥 VALIDASI DASAR
        if (empty($data['nik']) || empty($data['gaji_pokok'])) {
            $this->skipped++;
            return;
        }

        // 🔥 CEK PEGAWAI
        $pegawai = pegawaiModel::where('nik', $data['nik'])->first();

        if (!$pegawai) {
            $this->skipped++;
            return;
        }

        // 🔥 HITUNG MASA KERJA
        $masaKerja = $this->hitungMasaKerja($pegawai->mulai_kontrak);

        // 🔥 CEK DATA EXIST
        $existing = gapokModel::where('nik', $data['nik'])->first();

        // 🔥 UPSERT
        gapokModel::updateOrCreate(
            ['nik' => $data['nik']],
            [
                'nama' => $pegawai->nama,
                'jbtn' => $pegawai->jbtn,
                'stts_kerja' => $pegawai->stts_kerja,
                'mulai_kontrak' => $pegawai->mulai_kontrak, // 🔥 FIX
                'masa_kerja' => $masaKerja, // 🔥 AUTO HITUNG
                'gaji_pokok' => $data['gaji_pokok']
            ]
        );

        // 🔥 LOGIC COUNTER
        if ($existing) {

            // cek apakah ada perubahan
            if ($existing->gaji_pokok != $data['gaji_pokok']) {
                $this->updated++;
            } else {
                $this->skipped++; // tidak ada perubahan
            }

        } else {
            $this->added++;
        }
    }

    // 🔥 GETTER
    public function getAdded()
    {
        return $this->added;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getSkipped()
    {
        return $this->skipped;
    }

    public function create($data)
    {
        return $this->masterGapokRepository->createGapok($data);
    }

    public function findById($id)
    {
        return $this->masterGapokRepository->findById($id);
    }

    public function update($id, $data)
    {
        return $this->masterGapokRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->masterGapokRepository->delete($id);
    }

    public function syncFromPegawai()
    {
        $this->resetCounter();

        $pegawais = pegawaiModel::where('stts_aktif', 'AKTIF')->get();

        foreach ($pegawais as $pegawai) {

            $existing = gapokModel::where('nik', $pegawai->nik)->first();

            $masaKerja = $this->hitungMasaKerja($pegawai->mulai_kontrak);

            gapokModel::updateOrCreate(
                ['nik' => $pegawai->nik],
                [
                    'nama' => $pegawai->nama,
                    'jbtn' => $pegawai->jbtn,
                    'stts_kerja' => $pegawai->stts_kerja,
                    'mulai_kontrak' => $pegawai->mulai_kontrak,
                    'masa_kerja' => $masaKerja,
                    'gaji_pokok' => $existing->gaji_pokok ?? 0 // 🔥 tidak overwrite
                ]
            );

            if ($existing) {
                $this->updated++;
            } else {
                $this->added++;
            }
        }

        return [
            'added' => $this->added,
            'updated' => $this->updated,
            'skipped' => $this->skipped
        ];
    }
    
}
