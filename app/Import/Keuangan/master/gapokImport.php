<?php

namespace App\Import\Keuangan\master;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class GapokImport implements ToCollection
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
                'gaji_pokok' => $row[5] ?? null
            ];

            $this->service->prosesImportGapok($data);
        }
    }
}