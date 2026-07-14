<?php

namespace App\Export\Keuangan\master;

use App\Models\dbSimrs\gapokModel;
use App\Models\dbSimrs\jnsPotonganModel;
use App\Models\dbSimrs\potonganPegawaiModel;
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

        $potongan = jnsPotonganModel::select('id', 'kode', 'nama', 'tipe')
            ->orderBy('kode')
            ->get();

        $nominalPotongan = potonganPegawaiModel::select('nik', 'potongan_id', 'nominal')
            ->whereIn('nik', $data->pluck('nik'))
            ->whereIn('potongan_id', $potongan->pluck('id'))
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->nik.'|'.$item->potongan_id => $item->nominal,
            ]);

        $result = collect();

        foreach ($data as $item) {
            foreach ($potongan as $p) {
                $key = $item->nik.'|'.$p->id;
                $nominal = $nominalPotongan->has($key)
                    ? $nominalPotongan->get($key)
                    : ($p->tipe === 'persen_total_gaji' ? 0 : null);

                $result->push([
                    $item->nik,
                    $item->nama,
                    $item->jbtn,
                    $item->stts_kerja,
                    $item->gaji_pokok,
                    $p->kode,
                    $p->nama,
                    $nominal,
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
