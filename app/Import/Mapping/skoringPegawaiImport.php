<?php

namespace App\Import\Mapping;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class skoringPegawaiImport implements ToCollection
{
    protected $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    public function collection(Collection $rows)
    {
        $rows->shift();

        foreach ($rows as $row) {

            $data = [
                'nik' => $row[0] ?? null,
                'skor_id' => $row[5] ?? null,
            ];

            $this->service->prosesImportSkoringPegawai($data);
        }
    }
}