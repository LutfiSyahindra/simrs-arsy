<?php

namespace App\Services\masterData;

use App\Export\Keuangan\master\gapokExport;
use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use App\Repositories\masterData\masterGapokRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class masterGapokService
{
    protected $masterGapokRepository;

    public function __construct(masterGapokRepository $masterGapokRepository)
    {
        $this->masterGapokRepository = $masterGapokRepository;
    }

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

    protected $added = 0;
    protected $skipped = 0;

    public function resetCounter()
    {
        $this->added = 0;
        $this->skipped = 0;
    }

    public function prosesImportGapok($data)
    {
        if (!$data['nik'] || !$data['gaji_pokok']) {
            $this->skipped++;
            return;
        }

        $pegawai = pegawaiModel::where('nik', $data['nik'])->first();

        if (!$pegawai) {
            $this->skipped++;
            return;
        }

        $exists = gapokModel::where('nik', $data['nik'])->exists();

        gapokModel::updateOrCreate(
            ['nik' => $data['nik']],
            [
                'nama' => $pegawai->nama,
                'jbtn' => $pegawai->jbtn,
                'stts_kerja' => $pegawai->stts_kerja,
                'masa_kerja' => $pegawai->ms_kerja,
                'gaji_pokok' => $data['gaji_pokok']
            ]
        );

        if ($exists) {
            $this->skipped++; // dianggap update
        } else {
            $this->added++;
        }
    }

    public function getAdded()
    {
        return $this->added;
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
    
}
