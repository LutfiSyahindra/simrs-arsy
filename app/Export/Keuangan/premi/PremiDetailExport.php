<?php

namespace App\Export\Keuangan\premi;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PremiDetailExport implements FromArray, WithStyles, WithDrawings
{
    protected Collection $data;
    protected string $jenis;
    protected string $tglAwal;
    protected string $tglAkhir;
    protected ?string $nama;
    protected ?string $status;

    public function __construct(array $result)
    {
        $this->data     = $result['data'];
        $this->jenis    = $result['jenis'];
        $this->tglAwal  = $result['tgl_awal'];
        $this->tglAkhir = $result['tgl_akhir'];
        $this->nama     = $result['nama']   ?? '-';
        $this->status   = $result['status'] ?? '-';
    }

    /* =====================================================
     * DRAWING (LOGO RS)
     * ===================================================== */
    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo RS');
        $drawing->setDescription('Logo RS');
        $drawing->setPath(public_path('plugins/img/logoarsy.png'));
        $drawing->setHeight(65);
        $drawing->setCoordinates('A1');
        $drawing->setEditAs('oneCell');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);

        return $drawing;
    }

    /* =====================================================
     * DATA ARRAY
     * ===================================================== */
    public function array(): array
    {
        $rows = [];

        /* ================= HEADER ================= */
        $rows[] = ['', 'RS ABDURRAHMAN SYAMSYURI'];
        $rows[] = ['', 'LAPORAN DETAIL PREMI'];
        $rows[] = ['', 'Periode '.$this->tglAwal.' s/d '.$this->tglAkhir];
        $rows[] = [];

        /* ================= INFO ================= */
        $rows[] = ['Nama', $this->nama];
        $rows[] = ['Jenis', strtoupper($this->jenis)];
        $rows[] = ['Status', $this->status];
        $rows[] = ['Tanggal Cetak', now()->format('d-m-Y H:i')];
        $rows[] = [];

        /* =================================================
         * MODE KAMAR (SAMA DENGAN PDF)
         * ================================================= */
        if ($this->jenis === 'Kamar') {

            $rows[] = ['No','Tgl Masuk','Tgl Keluar','No Rawat','Lama','Premi'];

            $no = 1;
            foreach ($this->data as $item) {

                $tglMasuk = $item->tanggal
                    ? date('d-m-Y', strtotime($item->tanggal))
                    : '-';

                $tglKeluar = (
                    empty($item->tgl_keluar) ||
                    $item->tgl_keluar === '0000-00-00'
                )
                    ? '-'
                    : date('d-m-Y', strtotime($item->tgl_keluar));

                $rows[] = [
                    $no++,
                    $tglMasuk,
                    $tglKeluar,
                    $item->no_rawat,
                    $item->lama,
                    $item->nilai, // angka mentah
                ];
            }

            // GRAND TOTAL dihitung di PHP (AMAN, TANPA FORMULA)
            $rows[] = [
                '',
                '',
                '',
                '',
                'GRAND TOTAL',
                $this->data->sum('nilai')
            ];

            return $rows;
        }

        /* =================================================
         * MODE UMUM (DOKTER / PARAMEDIS)
         * ================================================= */
        foreach ($this->data->groupBy('layanan') as $layanan => $items) {

            $rows[] = [$layanan];
            $rows[] = ['No','Tanggal','No Rawat','Tindakan','Layanan','Sumber','Premi'];

            $no = 1;
            foreach ($items as $item) {
                $rows[] = [
                    $no++,
                    date('d-m-Y', strtotime($item->tanggal)),
                    $item->no_rawat,
                    $item->tindakan,
                    $item->layanan,
                    $item->sumber,
                    $item->nilai,
                ];
            }

            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                'Subtotal '.$layanan,
                '=SUMIF(E:E,"'.$layanan.'",G:G)'
            ];

            $rows[] = [];
        }

        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            'GRAND TOTAL',
            '=SUMIF(E:E,"<>",G:G)'
        ];

        return $rows;
    }

    /* =====================================================
     * STYLES
     * ===================================================== */
    public function styles(Worksheet $sheet)
    {
        // Kolom terakhir dinamis
        $lastCol = $this->jenis === 'Kamar' ? 'F' : 'G';

        /* ================= HEADER ================= */
        $sheet->mergeCells("B1:{$lastCol}1");
        $sheet->mergeCells("B2:{$lastCol}2");
        $sheet->mergeCells("B3:{$lastCol}3");

        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('B3')->getFont()->setItalic(true)->setSize(10);

        $sheet->getStyle("B1:{$lastCol}3")->getAlignment()
            ->setHorizontal('center')
            ->setVertical('center');

        /* ================= INFO BOX ================= */
        $sheet->mergeCells("C5:{$lastCol}5");
        $sheet->mergeCells("C6:{$lastCol}6");
        $sheet->mergeCells("C7:{$lastCol}7");
        $sheet->mergeCells("C8:{$lastCol}8");

        $sheet->getStyle('A5:A8')->getFont()->setBold(true);

        $sheet->getStyle("A5:{$lastCol}8")->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'F8F9FA']
            ],
            'borders' => [
                'outline' => ['borderStyle' => 'thin']
            ],
            'alignment' => [
                'vertical' => 'center'
            ]
        ]);

        /* ================= AUTO WIDTH ================= */
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        /* ================= FORMAT PREMI ================= */
        $premiCol = $this->jenis === 'Kamar' ? 'F' : 'G';

        $sheet->getStyle("{$premiCol}:{$premiCol}")
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        $sheet->getStyle("{$premiCol}:{$premiCol}")
            ->getAlignment()
            ->setHorizontal('right');

        /* ================= GRID ================= */
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A10:{$lastCol}{$highestRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle('hair');

        return [];
    }
}
