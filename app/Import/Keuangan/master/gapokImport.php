<?php

namespace App\Import\Keuangan\master;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class GapokImport implements ToCollection
{
    protected $service;
    public $errors = []; // 🔥 simpan error

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function collection(Collection $rows)
    {
        $rows->shift(); // skip header

        foreach ($rows as $index => $row) {

            $nik = $row[0] ?? null;
            $gaji = $row[5] ?? null;
            $noTelp = $row[6] ?? null;

            // 🔥 VALIDASI WAJIB
            if (empty($nik) || empty($gaji)) {
                $this->errors[] = "Baris " . ($index + 2) . " kosong / tidak lengkap";
                continue;
            }

            // 🔥 CEK PEGAWAI ADA
            $pegawai = pegawaiModel::where('nik', $nik)->first();

            if (!$pegawai) {
                $this->errors[] = "Baris " . ($index + 2) . " NIK tidak ditemukan: {$nik}";
                continue;
            }

            // 🔥 VALIDASI NUMERIC
            if (!is_numeric($gaji)) {
                $this->errors[] = "Baris " . ($index + 2) . " gaji tidak valid";
                continue;
            }

            try {

                // 🔥 AMBIL DATA DARI PEGAWAI (AMAN)
                $data = [
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                    'jbtn' => $pegawai->jbtn,
                    'stts_kerja' => $pegawai->stts_kerja,
                    'mulai_kontrak' => $pegawai->mulai_kontrak,
                    'gaji_pokok' => $gaji,
                    'no_telp' => $noTelp
                ];

                // 🔥 SIMPAN (CREATE / UPDATE)
                $this->service->prosesImportGapok($data);

            } catch (\Throwable $e) {

                $this->errors[] = "Baris " . ($index + 2) . " error: " . $e->getMessage();
            }
        }
    }
}