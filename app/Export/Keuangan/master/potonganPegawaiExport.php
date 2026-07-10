<?php

namespace App\Export\Keuangan\master;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsPotonganModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class potonganPegawaiExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $data = gapokModel::select(
            'nik',
            'nama',
            'jbtn',
            'stts_kerja',
            'gaji_pokok'
        )
            ->whereIn('stts_kerja', ['T', 'FT', 'PT'])
            ->orderBy('nama')
            ->get();

        $potongan = jnsPotonganModel::select('kode', 'nama', 'tipe')
            ->orderBy('kode')
            ->get();

        $result = collect();

        foreach ($data as $item) {
            foreach ($potongan as $p) {
                $result->push([
                    $item->nik,
                    $item->nama,
                    $item->jbtn,
                    $item->stts_kerja,
                    $item->gaji_pokok,
                    $p->kode,
                    $p->nama,
                    $p->tipe === 'persen_total_gaji' ? 0 : null,
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
            'Gaji Pokok',
            'Kode Potongan',
            'Nama Potongan',
            'Nominal',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
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
            'C' => 24,
            'D' => 18,
            'E' => 18,
            'F' => 20,
            'G' => 36,
            'H' => 20,
        ];
    }

    public function title(): string
    {
        return 'Template Potongan Pegawai';
    }
}
