<?php

namespace App\Export\Keuangan\master;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class tunjanganPegawaiExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{

    public function collection()
    {
        $data = gapokModel::select(
            'nik',
            'nama',
            'jbtn',
            'stts_kerja',
            'masa_kerja'
        )->get();

        $result = collect();

        foreach ($data as $item) {

        for ($i = 0; $i < 5; $i++) {

            $result->push([
                $item->nik,
                $item->nama,
                $item->jbtn,
                $item->stts_kerja,
                $item->masa_kerja,
            ]);
        }
    }

    return $result;
    }

    public function headings(): array
    {
        return [
            'Nik',
            'Nama',
            'Jabatan',
            'Status Kerja',
            'Masa Kerja',
            'Tunjangan_id'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'D9D9D9'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 40,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
        ];
    }

    public function title(): string
    {
        return 'Template Tunjangan Pegawai';
    }
}