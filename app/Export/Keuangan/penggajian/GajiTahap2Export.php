<?php

namespace App\Export\Keuangan\penggajian;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GajiTahap2Export implements WithMultipleSheets
{
    public function __construct(private readonly array $payload) {}

    public function sheets(): array
    {
        return [
            new GajiTahap2SummarySheet(
                $this->payload['periode'],
                collect($this->payload['rows'] ?? []),
                $this->payload['summary'] ?? []
            ),
            new GajiTahap2DetailSheet(
                $this->payload['periode'],
                collect($this->payload['details'] ?? [])
            ),
        ];
    }
}

class GajiTahap2SummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $periode,
        private readonly Collection $rows,
        private readonly array $summary
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Periode',
            'NIK',
            'Nama',
            'Jabatan',
            'Status',
            'Gaji Pokok',
            'Gaji Dibayarkan',
            'Total Premi',
            'Jumlah Sumber Premi',
            'Total Diterima',
        ];
    }

    public function map($row): array
    {
        return [
            $row['periode'] ?? $this->periode,
            $row['nik'] ?? '',
            $row['nama_pegawai'] ?? '',
            $row['jabatan'] ?? '',
            $row['status_label'] ?? ($row['status'] ?? ''),
            (int) ($row['gaji_pokok'] ?? 0),
            (int) ($row['gaji_dibayarkan'] ?? 0),
            (int) ($row['total_premi'] ?? 0),
            (int) ($row['jumlah_sumber_premi'] ?? 0),
            (int) ($row['total'] ?? 0),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal('center');

        $summaryRow = $this->rows->count() + 3;
        $sheet->setCellValue("A{$summaryRow}", 'TOTAL');
        $sheet->setCellValue("B{$summaryRow}", (int) ($this->summary['jumlah_pegawai'] ?? 0).' pegawai');
        $sheet->setCellValue("G{$summaryRow}", (int) ($this->summary['total_gapok'] ?? 0));
        $sheet->setCellValue("H{$summaryRow}", (int) ($this->summary['total_premi'] ?? 0));
        $sheet->setCellValue("J{$summaryRow}", (int) ($this->summary['total_gaji'] ?? 0));
        $sheet->getStyle("A{$summaryRow}:J{$summaryRow}")->getFont()->setBold(true);

        return [];
    }

    public function title(): string
    {
        return 'Gaji Tahap 2';
    }
}

class GajiTahap2DetailSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $periode,
        private readonly Collection $details
    ) {}

    public function collection(): Collection
    {
        return $this->details;
    }

    public function headings(): array
    {
        return [
            'Periode',
            'NIK',
            'Nama',
            'Jabatan',
            'Status',
            'Sumber Premi',
            'Role',
            'Nominal',
        ];
    }

    public function map($row): array
    {
        return [
            $row->periode ?? $this->periode,
            $row->nik ?? '',
            $row->nama ?? '',
            $row->jabatan ?? '',
            $row->status ?? '',
            $row->source_label ?? '',
            $row->role_label ?? '',
            (int) ($row->nominal ?? 0),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');

        return [];
    }

    public function title(): string
    {
        return 'Detail Premi';
    }
}
