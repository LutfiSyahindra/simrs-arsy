<?php

namespace App\Import\Mapping;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class unitPegawaiImport implements ToCollection
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
                'unit_kode' => $row[5] ?? null,
            ];

            $this->service->prosesImportUnitPegawai($data);
        }
    }
}
