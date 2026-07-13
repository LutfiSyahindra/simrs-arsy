<?php

namespace App\Export\Keuangan\penggajian;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GajiTahap2Export implements WithMultipleSheets
{
    public function __construct(private readonly array $payload) {}

    public function sheets(): array
    {
        $jenis = $this->payload['jenis'] ?? 'tahap2';
        $periode = $this->payload['periode'];
        $stage1 = $this->payload['stage1'] ?? [
            'periode' => $periode,
            'summary' => [],
            'rows' => [],
        ];
        $stage2 = $this->payload['stage2'] ?? [
            'periode' => $periode,
            'summary' => $this->payload['summary'] ?? [],
            'rows' => $this->payload['rows'] ?? [],
            'details' => $this->payload['details'] ?? [],
        ];

        if ($jenis === 'tahap1') {
            return [
                new GajiTahap1SummarySheet(
                    $stage1['periode'] ?? $periode,
                    collect($stage1['rows'] ?? []),
                    $stage1['summary'] ?? []
                ),
            ];
        }

        if ($jenis === 'keseluruhan') {
            $combined = $this->payload['combined'] ?? [
                'periode' => $periode,
                'summary' => [],
                'rows' => [],
            ];

            return [
                new GajiKeseluruhanSummarySheet(
                    $combined['periode'] ?? $periode,
                    collect($combined['rows'] ?? []),
                    $combined['summary'] ?? []
                ),
                new GajiTahap1SummarySheet(
                    $stage1['periode'] ?? $periode,
                    collect($stage1['rows'] ?? []),
                    $stage1['summary'] ?? []
                ),
                new GajiTahap2SummarySheet(
                    $stage2['periode'] ?? $periode,
                    collect($stage2['rows'] ?? []),
                    $stage2['summary'] ?? []
                ),
                new GajiTahap2DetailSheet(
                    $stage2['periode'] ?? $periode,
                    collect($stage2['details'] ?? [])
                ),
            ];
        }

        return [
            new GajiTahap2SummarySheet(
                $stage2['periode'] ?? $periode,
                collect($stage2['rows'] ?? []),
                $stage2['summary'] ?? []
            ),
            new GajiTahap2DetailSheet(
                $stage2['periode'] ?? $periode,
                collect($stage2['details'] ?? [])
            ),
        ];
    }
}

trait FormatsPayrollWorkbook
{
    private function printedAt(): string
    {
        return now()->format('d/m/Y H:i');
    }

    private function moneyFormat(): string
    {
        return '"Rp" #,##0;[Red]-"Rp" #,##0;"Rp" 0';
    }

    private function formatRupiah(int|float|null $value): string
    {
        return 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
    }

    private function readableStatus(?string $status): string
    {
        $status = strtoupper(trim((string) $status));

        return match ($status) {
            'T' => 'Pegawai Tetap',
            'FT' => 'Pegawai Kontrak',
            'PT' => 'Pegawai Casual',
            'MT' => 'Mitra',
            default => $status ?: '-',
        };
    }

    private function applyWorkbookShell(Worksheet $sheet, string $lastColumn, string $title, string $subtitle): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getParent()?->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.35)
            ->setRight(0.25)
            ->setBottom(0.35)
            ->setLeft(0.25);

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', $subtitle.' | Dicetak: '.$this->printedAt());

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '475569'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function applySummaryCard(Worksheet $sheet, string $rangeLabel, string $rangeValue, string $label, string|int $value): void
    {
        $sheet->mergeCells($rangeLabel);
        $sheet->mergeCells($rangeValue);
        $sheet->setCellValue(explode(':', $rangeLabel)[0], $label);
        $sheet->setCellValue(explode(':', $rangeValue)[0], $value);

        $sheet->getStyle($rangeLabel)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 8,
                'color' => ['rgb' => '64748B'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
        $sheet->getStyle($rangeValue)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);
    }

    private function applySectionLabel(Worksheet $sheet, string $range, string $label): void
    {
        $sheet->mergeCells($range);
        $sheet->setCellValue(explode(':', $range)[0], $label);
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function applyTableStyle(Worksheet $sheet, string $range, string $headerRange): void
    {
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1D4ED8'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function applyTotalRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '065F46'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECFDF5'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'A7F3D0'],
                ],
            ],
        ]);
    }

    private function sumFormula(string $column, int $firstRow, int $lastRow): string|int
    {
        return $lastRow >= $firstRow
            ? "=SUM({$column}{$firstRow}:{$column}{$lastRow})"
            : 0;
    }
}

class GajiTahap1SummarySheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsPayrollWorkbook;

    private int $rowNumber = 0;

    public function __construct(
        private readonly string $periode,
        private readonly Collection $rows,
        private readonly array $summary
    ) {}

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->rows;
    }

    public function startCell(): string
    {
        return 'A9';
    }

    public function headings(): array
    {
        return [
            'No',
            'Periode',
            'NIK',
            'Nama Pegawai',
            'Jabatan',
            'Status',
            'Komponen Gaji',
            'Gaji Pokok',
            'Komponen Dibayar',
            'Gaji Dibayarkan',
            'Tunjangan',
            'Premi',
            'Total Diterima',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row['periode'] ?? $this->periode,
            $row['nik'] ?? '',
            $row['nama_pegawai'] ?? '',
            $row['jabatan'] ?? '',
            $this->readableStatus($row['status'] ?? null),
            $row['komponen_gaji_label'] ?? 'Gaji Pokok',
            (int) ($row['gapok'] ?? 0),
            $row['komponen_gaji_dibayar_label'] ?? 'Gaji Dibayarkan',
            (int) ($row['gaji_dibayarkan'] ?? 0),
            (int) ($row['tunjangan'] ?? 0),
            (int) ($row['premi'] ?? 0),
            (int) ($row['total'] ?? 0),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 13,
            'C' => 18,
            'D' => 28,
            'E' => 28,
            'F' => 19,
            'G' => 22,
            'H' => 17,
            'I' => 22,
            'J' => 18,
            'K' => 18,
            'L' => 18,
            'M' => 19,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 9;
                $firstDataRow = 10;
                $lastDataRow = $firstDataRow + $this->rows->count() - 1;
                $lastTableRow = max($headerRow, $lastDataRow);
                $totalRow = $headerRow + $this->rows->count() + 2;

                $this->applyWorkbookShell(
                    $sheet,
                    'M',
                    'LAPORAN GAJI TAHAP 1',
                    'Periode '.$this->periode
                );
                $this->applySummaryCard($sheet, 'A4:C4', 'A5:C5', 'TOTAL PEGAWAI', (int) ($this->summary['jumlah_pegawai'] ?? 0).' pegawai');
                $this->applySummaryCard($sheet, 'D4:F4', 'D5:F5', 'TOTAL GAJI', $this->formatRupiah($this->summary['total_gaji'] ?? 0));
                $this->applySummaryCard($sheet, 'G4:H4', 'G5:H5', 'GAJI DIBAYARKAN', $this->formatRupiah($this->summary['total_gapok'] ?? 0));
                $this->applySummaryCard($sheet, 'I4:J4', 'I5:J5', 'TUNJANGAN', $this->formatRupiah($this->summary['total_tunjangan'] ?? 0));
                $this->applySummaryCard($sheet, 'K4:L4', 'K5:L5', 'PREMI', $this->formatRupiah($this->summary['total_premi'] ?? 0));
                $this->applySummaryCard(
                    $sheet,
                    'M4:M4',
                    'M5:M5',
                    'STATUS',
                    (int) ($this->summary['jumlah_tetap'] ?? 0).' tetap / '.(int) ($this->summary['jumlah_kontrak'] ?? 0).' kontrak'
                );
                $this->applySectionLabel($sheet, 'A7:M7', 'RINCIAN GAJI TAHAP 1 PER PEGAWAI');
                $this->applyTableStyle($sheet, "A{$headerRow}:M{$lastTableRow}", "A{$headerRow}:M{$headerRow}");

                $sheet->setAutoFilter("A{$headerRow}:M{$lastTableRow}");
                $sheet->freezePane("A{$firstDataRow}");
                $sheet->getRowDimension($headerRow)->setRowHeight(30);
                $sheet->getStyle("A{$firstDataRow}:M".max($firstDataRow, $lastDataRow))->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$firstDataRow}:C".max($firstDataRow, $lastDataRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$firstDataRow}:H{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("J{$firstDataRow}:M{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("H{$firstDataRow}:M{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL KESELURUHAN');
                $sheet->setCellValue("H{$totalRow}", $this->sumFormula('H', $firstDataRow, $lastDataRow));
                $sheet->setCellValue("J{$totalRow}", (int) ($this->summary['total_gapok'] ?? 0));
                $sheet->setCellValue("K{$totalRow}", (int) ($this->summary['total_tunjangan'] ?? 0));
                $sheet->setCellValue("L{$totalRow}", (int) ($this->summary['total_premi'] ?? 0));
                $sheet->setCellValue("M{$totalRow}", (int) ($this->summary['total_gaji'] ?? 0));
                $this->applyTotalRow($sheet, "A{$totalRow}:M{$totalRow}");
            },
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Tahap 1';
    }
}

class GajiKeseluruhanSummarySheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsPayrollWorkbook;

    private int $rowNumber = 0;

    public function __construct(
        private readonly string $periode,
        private readonly Collection $rows,
        private readonly array $summary
    ) {}

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->rows;
    }

    public function startCell(): string
    {
        return 'A9';
    }

    public function headings(): array
    {
        return [
            'No',
            'Periode',
            'NIK',
            'Nama Pegawai',
            'Jabatan',
            'Status',
            'Tahap 1 Gaji',
            'Tahap 1 Tunjangan',
            'Tahap 1 Premi',
            'Total Tahap 1',
            'Tahap 2 Gaji',
            'Tahap 2 Premi',
            'Tahap 2 Potongan',
            'Total Tahap 2',
            'Total Diterima',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row['periode'] ?? $this->periode,
            $row['nik'] ?? '',
            $row['nama_pegawai'] ?? '',
            $row['jabatan'] ?? '',
            $row['status_label'] ?? $this->readableStatus($row['status'] ?? null),
            (int) ($row['tahap1_gaji_dibayarkan'] ?? 0),
            (int) ($row['tahap1_tunjangan'] ?? 0),
            (int) ($row['tahap1_premi'] ?? 0),
            (int) ($row['total_tahap1'] ?? 0),
            (int) ($row['tahap2_gaji_dibayarkan'] ?? 0),
            (int) ($row['tahap2_premi'] ?? 0),
            (int) ($row['tahap2_potongan'] ?? 0),
            (int) ($row['total_tahap2'] ?? 0),
            (int) ($row['total_diterima'] ?? 0),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 13,
            'C' => 18,
            'D' => 28,
            'E' => 28,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 18,
            'L' => 18,
            'M' => 18,
            'N' => 18,
            'O' => 19,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 9;
                $firstDataRow = 10;
                $lastDataRow = $firstDataRow + $this->rows->count() - 1;
                $lastTableRow = max($headerRow, $lastDataRow);
                $totalRow = $headerRow + $this->rows->count() + 2;

                $this->applyWorkbookShell(
                    $sheet,
                    'O',
                    'LAPORAN GAJI KESELURUHAN',
                    'Tahap 1 + Tahap 2 | Periode '.$this->periode
                );
                $this->applySummaryCard($sheet, 'A4:C4', 'A5:C5', 'TOTAL PEGAWAI', (int) ($this->summary['jumlah_pegawai'] ?? 0).' pegawai');
                $this->applySummaryCard($sheet, 'D4:F4', 'D5:F5', 'TOTAL DITERIMA', $this->formatRupiah($this->summary['total_keseluruhan'] ?? 0));
                $this->applySummaryCard($sheet, 'G4:I4', 'G5:I5', 'TOTAL TAHAP 1', $this->formatRupiah($this->summary['total_tahap1'] ?? 0));
                $this->applySummaryCard($sheet, 'J4:L4', 'J5:L5', 'TOTAL TAHAP 2', $this->formatRupiah($this->summary['total_tahap2'] ?? 0));
                $this->applySummaryCard($sheet, 'M4:O4', 'M5:O5', 'POTONGAN TAHAP 2', $this->formatRupiah($this->summary['total_potongan_tahap2'] ?? 0));
                $this->applySectionLabel($sheet, 'A7:O7', 'TOTAL GAJI TAHAP 1 + TAHAP 2 PER PEGAWAI');
                $this->applyTableStyle($sheet, "A{$headerRow}:O{$lastTableRow}", "A{$headerRow}:O{$headerRow}");

                $sheet->setAutoFilter("A{$headerRow}:O{$lastTableRow}");
                $sheet->freezePane("A{$firstDataRow}");
                $sheet->getRowDimension($headerRow)->setRowHeight(34);
                $sheet->getStyle("A{$firstDataRow}:O".max($firstDataRow, $lastDataRow))->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$firstDataRow}:C".max($firstDataRow, $lastDataRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$firstDataRow}:O{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("G{$firstDataRow}:O{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL KESELURUHAN');
                foreach (range('G', 'O') as $column) {
                    $sheet->setCellValue("{$column}{$totalRow}", $this->sumFormula($column, $firstDataRow, $lastDataRow));
                }
                $this->applyTotalRow($sheet, "A{$totalRow}:O{$totalRow}");
            },
        ];
    }

    public function title(): string
    {
        return 'Keseluruhan';
    }
}

class GajiTahap2SummarySheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsPayrollWorkbook;

    private int $rowNumber = 0;

    public function __construct(
        private readonly string $periode,
        private readonly Collection $rows,
        private readonly array $summary
    ) {}

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->rows;
    }

    public function startCell(): string
    {
        return 'A9';
    }

    public function headings(): array
    {
        return [
            'No',
            'Periode',
            'NIK',
            'Nama Pegawai',
            'Jabatan',
            'Status',
            'Komponen Gaji',
            'Gaji Pokok',
            'Komponen Dibayar',
            'Gaji Dibayarkan',
            'Total Premi',
            'Total Potongan',
            'Sumber',
            'Rincian Premi',
            'Total Diterima',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row['periode'] ?? $this->periode,
            $row['nik'] ?? '',
            $row['nama_pegawai'] ?? '',
            $row['jabatan'] ?? '',
            $row['status_label'] ?? $this->readableStatus($row['status'] ?? null),
            $row['komponen_gaji_label'] ?? 'Gaji Pokok',
            (int) ($row['gaji_pokok'] ?? 0),
            $row['komponen_gaji_dibayar_label'] ?? 'Komponen Gaji',
            (int) ($row['gaji_dibayarkan'] ?? 0),
            (int) ($row['total_premi'] ?? 0),
            (int) ($row['total_potongan'] ?? 0),
            (int) ($row['jumlah_sumber_premi'] ?? 0),
            $this->premiumBreakdownText($row['premi_breakdown'] ?? []),
            (int) ($row['total'] ?? 0),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 13,
            'C' => 18,
            'D' => 28,
            'E' => 28,
            'F' => 19,
            'G' => 22,
            'H' => 17,
            'I' => 22,
            'J' => 18,
            'K' => 18,
            'L' => 18,
            'M' => 10,
            'N' => 42,
            'O' => 19,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 9;
                $firstDataRow = 10;
                $lastDataRow = $firstDataRow + $this->rows->count() - 1;
                $lastTableRow = max($headerRow, $lastDataRow);
                $totalRow = $headerRow + $this->rows->count() + 2;

                $this->applyWorkbookShell(
                    $sheet,
                    'O',
                    'LAPORAN GAJI TAHAP 2',
                    'Periode '.$this->periode
                );
                $this->applySummaryCard($sheet, 'A4:C4', 'A5:C5', 'TOTAL PEGAWAI', (int) ($this->summary['jumlah_pegawai'] ?? 0).' pegawai');
                $this->applySummaryCard($sheet, 'D4:F4', 'D5:F5', 'TOTAL GAJI', $this->formatRupiah($this->summary['total_gaji'] ?? 0));
                $this->applySummaryCard($sheet, 'G4:H4', 'G5:H5', 'GAJI DIBAYARKAN', $this->formatRupiah($this->summary['total_gapok'] ?? 0));
                $this->applySummaryCard($sheet, 'I4:J4', 'I5:J5', 'TOTAL PREMI', $this->formatRupiah($this->summary['total_premi'] ?? 0));
                $this->applySummaryCard($sheet, 'K4:L4', 'K5:L5', 'TOTAL POTONGAN', $this->formatRupiah($this->summary['total_potongan'] ?? 0));
                $this->applySummaryCard(
                    $sheet,
                    'M4:O4',
                    'M5:O5',
                    'STATUS',
                    (int) ($this->summary['jumlah_tetap'] ?? 0).' tetap / '.(int) ($this->summary['jumlah_kontrak'] ?? 0).' kontrak'
                );
                $this->applySectionLabel($sheet, 'A7:O7', 'RINCIAN PEMBAYARAN PER PEGAWAI');
                $this->applyTableStyle($sheet, "A{$headerRow}:O{$lastTableRow}", "A{$headerRow}:O{$headerRow}");

                $sheet->setAutoFilter("A{$headerRow}:O{$lastTableRow}");
                $sheet->freezePane("A{$firstDataRow}");
                $sheet->getRowDimension($headerRow)->setRowHeight(30);
                $sheet->getStyle("A{$firstDataRow}:O".max($firstDataRow, $lastDataRow))->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$firstDataRow}:C".max($firstDataRow, $lastDataRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$firstDataRow}:H{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("J{$firstDataRow}:L{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("O{$firstDataRow}:O{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("H{$firstDataRow}:O{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL KESELURUHAN');
                $sheet->setCellValue("H{$totalRow}", $this->sumFormula('H', $firstDataRow, $lastDataRow));
                $sheet->setCellValue("J{$totalRow}", (int) ($this->summary['total_gapok'] ?? 0));
                $sheet->setCellValue("K{$totalRow}", (int) ($this->summary['total_premi'] ?? 0));
                $sheet->setCellValue("L{$totalRow}", (int) ($this->summary['total_potongan'] ?? 0));
                $sheet->setCellValue("O{$totalRow}", (int) ($this->summary['total_gaji'] ?? 0));
                $this->applyTotalRow($sheet, "A{$totalRow}:O{$totalRow}");
            },
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Tahap 2';
    }

    private function premiumBreakdownText(array|Collection $items): string
    {
        return collect($items)
            ->map(function ($item) {
                $source = $item['source_label'] ?? 'Premi';
                $total = $this->formatRupiah((int) ($item['total'] ?? 0));
                $count = (int) ($item['jumlah_data'] ?? 0);

                return trim($source.' - '.$total.' ('.$count.' data)');
            })
            ->filter()
            ->implode("\n");
    }
}

class GajiTahap2DetailSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use FormatsPayrollWorkbook;

    private int $rowNumber = 0;

    public function __construct(
        private readonly string $periode,
        private readonly Collection $details
    ) {}

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->details;
    }

    public function startCell(): string
    {
        return 'A9';
    }

    public function headings(): array
    {
        return [
            'No',
            'Periode',
            'NIK',
            'Nama Pegawai',
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
            ++$this->rowNumber,
            $row->periode ?? $this->periode,
            $row->nik ?? '',
            $row->nama ?? '',
            $row->jabatan ?? '',
            $this->readableStatus($row->status ?? null),
            $row->source_label ?? '',
            $row->role_label ?? '',
            (int) ($row->nominal ?? 0),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 13,
            'C' => 18,
            'D' => 30,
            'E' => 30,
            'F' => 19,
            'G' => 34,
            'H' => 24,
            'I' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 9;
                $firstDataRow = 10;
                $lastDataRow = $firstDataRow + $this->details->count() - 1;
                $lastTableRow = max($headerRow, $lastDataRow);
                $totalRow = $headerRow + $this->details->count() + 2;

                $this->applyWorkbookShell(
                    $sheet,
                    'I',
                    'DETAIL PREMI GAJI TAHAP 2',
                    'Periode '.$this->periode
                );
                $this->applySummaryCard($sheet, 'A4:C4', 'A5:C5', 'TOTAL BARIS', $this->details->count().' detail');
                $this->applySummaryCard($sheet, 'D4:F4', 'D5:F5', 'PEGAWAI UNIK', $this->details->pluck('nik')->filter()->unique()->count().' pegawai');
                $this->applySummaryCard($sheet, 'G4:I4', 'G5:I5', 'TOTAL PREMI', $this->formatRupiah((int) $this->details->sum('nominal')));
                $this->applySectionLabel($sheet, 'A7:I7', 'RINCIAN SUMBER PREMI');
                $this->applyTableStyle($sheet, "A{$headerRow}:I{$lastTableRow}", "A{$headerRow}:I{$headerRow}");

                $sheet->setAutoFilter("A{$headerRow}:I{$lastTableRow}");
                $sheet->freezePane("A{$firstDataRow}");
                $sheet->getRowDimension($headerRow)->setRowHeight(30);
                $sheet->getStyle("A{$firstDataRow}:I".max($firstDataRow, $lastDataRow))->getAlignment()->setWrapText(true);
                $sheet->getStyle("A{$firstDataRow}:C".max($firstDataRow, $lastDataRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("I{$firstDataRow}:I{$totalRow}")->getNumberFormat()->setFormatCode($this->moneyFormat());
                $sheet->getStyle("I{$firstDataRow}:I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->mergeCells("A{$totalRow}:H{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL PREMI');
                $sheet->setCellValue("I{$totalRow}", $this->sumFormula('I', $firstDataRow, $lastDataRow));
                $this->applyTotalRow($sheet, "A{$totalRow}:I{$totalRow}");
            },
        ];
    }

    public function title(): string
    {
        return 'Detail Premi';
    }
}
