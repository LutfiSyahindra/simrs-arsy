<?php

namespace App\Export\Keuangan\master;

use App\Models\dbKhanza\pegawaiModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class gapokExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{

    public function collection()
    {
        $data = pegawaiModel::select(
            'nik',
            'nama',
            'jbtn',
            'stts_kerja',
            'ms_kerja'
        )->where('stts_aktif', '=', 'AKTIF')->get();

        return $data->map(function ($item) {
            return [
                $item->nik,
                $item->nama,
                $item->jbtn,
                $item->stts_kerja,
                $item->ms_kerja,
                '' // Gaji Pokok kosong
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nik',
            'Nama',
            'Jabatan',
            'Status Kerja',
            'Masa Kerja',
            'Gaji Pokok',
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
            'C' => 40,
            'D' => 20,
            'E' => 25,
            'F' => 25, // Gaji Pokok
        ];
    }

    public function title(): string
    {
        return 'Template Gaji Pokok';
    }
}