<?php

namespace App\Export\Keuangan\master;

use App\Models\dbKhanza\pegawaiModel;
use App\Models\dbSimrs\gapokModel;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class gapokExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $data = pegawaiModel::select(
            'nik',
            'nama',
            'jbtn',
            'stts_kerja',
            'mulai_kontrak'
        )->where('stts_aktif', 'AKTIF')->get();

        $selectGapok = ['nik', 'gaji_pokok', 'no_telp'];

        if (Schema::hasColumn('gaji_pokok', 'email')) {
            $selectGapok[] = 'email';
        }

        $existingGapok = gapokModel::select($selectGapok)
            ->whereIn('nik', $data->pluck('nik'))
            ->get()
            ->keyBy('nik');

        return $data->map(function ($item) use ($existingGapok) {
            $gapok = $existingGapok->get($item->nik);

            return [
                $item->nik,
                $item->nama,
                $item->jbtn,
                $item->stts_kerja,
                $item->mulai_kontrak,
                $gapok?->gaji_pokok ?? '',
                $gapok?->no_telp ?? '',
                $gapok?->email ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'nik',
            'nama',
            'jbtn',
            'stts_kerja',
            'mulai_kontrak',
            'gaji_pokok_atau_upah_str',
            'no_telp',
            'email',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // 🔥 STYLE HEADER
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
        ]);

        // 🔥 FORMAT TANGGAL (mulai_kontrak)
        $sheet->getStyle("E2:E{$highestRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD2);

        // 🔥 HIGHLIGHT KOLOM GAJI
        $sheet->getStyle("F1:F{$highestRow}")->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'FFF3CD'], // kuning soft
            ],
        ]);

        // 🔥 LOCK KOLOM A-E
        $sheet->getStyle("A2:E{$highestRow}")
            ->getProtection()
            ->setLocked(true);

        // 🔥 UNLOCK KOLOM GAJI (F)
        $sheet->getStyle("F2:F{$highestRow}")
            ->getProtection()
            ->setLocked(false);

        // 🔥 UNLOCK KOLOM GAJI (F)
        $sheet->getStyle("G2:G{$highestRow}")
            ->getProtection()
            ->setLocked(false);

        $sheet->getStyle("H2:H{$highestRow}")
            ->getProtection()
            ->setLocked(false);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 35,
            'C' => 35,
            'D' => 20,
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 35,
        ];
    }

    public function title(): string
    {
        return 'Template Gaji Pokok';
    }
}
