@php
    $slip = $data['doctor_slip'] ?? [];
    $formatAmount = static function ($amount, bool $dashZero = true) {
        if ($amount === null) {
            return '-';
        }

        $amount = (int) $amount;

        if ($dashZero && $amount === 0) {
            return '-';
        }

        return number_format($amount, 0, ',', '.');
    };
    $formatJml = static fn($value) => $value === null || $value === '' ? '' : $value;
    $rowMuted = static function ($row) {
        $nominal = (int) ($row['nominal'] ?? 0);
        $jml = $row['jml'] ?? null;

        return $nominal === 0 && ($jml === null || $jml === '' || (int) $jml === 0) ? ' row-muted' : '';
    };
    $paperSize = $paperSize ?? [];
    $hasCustomPaper = !empty($paperSize);
    $paperWidthMm = (float) ($paperSize['width_mm'] ?? 136);
    $paperHeightMm = (float) ($paperSize['height_mm'] ?? 172);
    $customPageMarginMm = 3;
    $customSlipWidthMm = max(110, $paperWidthMm - (($customPageMarginMm * 2) + 8));
@endphp

<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <style>
            @page {
                @if ($hasCustomPaper)
                    size: {{ $paperWidthMm }}mm {{ $paperHeightMm }}mm;
                    margin: {{ $customPageMarginMm }}mm;
                @else
                    size: A4 portrait;
                    margin: 9mm 8mm;
                @endif
            }

            html,
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 7.4px;
                color: #111827;
                margin: 0;
                page-break-after: avoid;
            }

            .slip-wrap {
                width: 124mm;
                margin: 0 auto;
                border: 1px solid #24382f;
                padding: 4mm;
                background: #fff;
                page-break-inside: avoid;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            td,
            th {
                border: .55px solid #8f9a96;
                padding: 1.6px 3.2px;
                line-height: 1.22;
                vertical-align: middle;
            }

            .header-table {
                margin-bottom: 2mm;
            }

            .header-table td {
                border: 0;
                padding: 0;
                color: #153c2e;
                font-size: 7.2px;
                font-weight: bold;
            }

            .logo-cell {
                width: 15mm;
                text-align: center;
                vertical-align: middle;
            }

            .logo {
                width: 11.5mm;
            }

            .hospital-cell {
                width: 72mm;
                padding-left: 2mm !important;
                line-height: 1.25;
                vertical-align: middle;
            }

            .hospital-name {
                font-size: 8.6px;
                letter-spacing: .15px;
            }

            .hospital-address {
                margin-top: .6mm;
                color: #4b5563;
                font-size: 6.8px;
                font-weight: normal;
            }

            .title-cell {
                width: 29mm;
                text-align: right;
                font-weight: bold;
                vertical-align: middle;
            }

            .slip-title {
                display: inline-block;
                min-width: 23mm;
                padding: 1.6mm 2mm;
                border: .7px solid #153c2e;
                background: #eaf3ef;
                color: #153c2e;
                text-align: center;
                font-size: 8.2px;
                letter-spacing: .2px;
            }

            .slip-subtitle {
                margin-top: .8mm;
                color: #4b5563;
                font-size: 6.6px;
                font-weight: normal;
                text-align: center;
            }

            .meta-table {
                margin-bottom: 2mm;
                border: .7px solid #8f9a96;
            }

            .meta-table td {
                border: 0;
                padding: 1.7px 3px;
                font-size: 7px;
            }

            .meta-label {
                width: 13mm;
                color: #334155;
                font-weight: bold;
            }

            .meta-colon {
                width: 2mm;
                text-align: center;
            }

            .meta-value {
                width: 52mm;
                color: #111827;
                font-weight: bold;
            }

            .meta-side-label {
                width: 15mm;
                color: #334155;
                text-align: right;
                font-weight: bold;
            }

            .meta-side-value {
                width: 30mm;
                color: #111827;
                font-weight: bold;
            }

            .salary-table {
                table-layout: fixed;
            }

            .salary-table th {
                background: #153c2e;
                color: #fff;
                font-size: 7px;
                text-align: center;
                font-weight: bold;
                letter-spacing: .2px;
            }

            .item-col {
                width: 56%;
            }

            .jml-col {
                width: 9%;
                text-align: center;
            }

            .rp-col {
                width: 7%;
                text-align: center;
                white-space: nowrap;
            }

            .amount-col {
                width: 28%;
                text-align: right;
                white-space: nowrap;
            }

            .item-text {
                padding-left: 8px;
            }

            .detail-text {
                padding-left: 16px;
                color: #475569;
                font-size: 6.9px;
            }

            .source-text {
                display: block;
                margin-top: .5px;
                color: #64748b;
                font-size: 6.2px;
                font-weight: normal;
                line-height: 1.12;
            }

            .line-row td {
                background: #fff;
            }

            .row-muted td {
                color: #6b7280;
            }

            .group-row td {
                background: #eef5f1;
                color: #153c2e;
                font-weight: bold;
                border-top: .8px solid #24382f;
            }

            .total-row td {
                background: #f3f4f6;
                font-weight: bold;
                border-top: .8px solid #24382f;
            }

            .total-label {
                text-align: center;
            }

            .grand-row td {
                background: #eaf3ef;
                color: #153c2e;
                font-weight: bold;
                text-align: center;
            }

            .final-row td {
                background: #153c2e;
                color: #fff;
            }

            .grand-row .rp-col,
            .grand-row .amount-col {
                text-align: right;
            }

            .paid-source td {
                background: #f3f4f6;
                color: #334155;
                font-weight: bold;
                font-style: italic;
                letter-spacing: .2px;
                text-align: center;
                padding: 2.4px 3px;
            }

            .signature {
                border-top: 0;
                margin-top: 1mm;
            }

            .signature td {
                border: 0;
                height: 20mm;
                text-align: center;
                vertical-align: top;
                color: #334155;
                font-size: 7px;
            }

            .signature .role {
                display: block;
                margin-top: 10mm;
                color: #111827;
                font-weight: bold;
            }

            @if ($hasCustomPaper)
                .slip-wrap {
                    box-sizing: border-box;
                    width: {{ $customSlipWidthMm }}mm;
                    max-width: {{ $customSlipWidthMm }}mm;
                    margin: 0 auto;
                    padding: 2.5mm;
                }

                .header-table {
                    margin-bottom: 1.6mm;
                }

                .meta-table {
                    margin-bottom: 1.6mm;
                }

                .signature td {
                    height: 18mm;
                }
            @endif
        </style>
    </head>

    <body>
        <div class="slip-wrap">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img class="logo" src="{{ public_path('plugins/img/logoarsy.png') }}" alt="Logo">
                    </td>
                    <td class="hospital-cell">
                        <div class="hospital-name">RUMAH SAKIT ABDURRAHMAN SYAMSURI (RS. ARSY)</div>
                        <div class="hospital-address">PACIRAN - LAMONGAN - JAWA TIMUR</div>
                    </td>
                    <td class="title-cell">
                        <div class="slip-title">SLIP GAJI</div>
                        <div class="slip-subtitle">DOKTER</div>
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">NIK</td>
                    <td class="meta-colon">:</td>
                    <td class="meta-value">{{ $slip['nik'] ?? '-' }}</td>
                    <td class="meta-side-label">Bulan :</td>
                    <td class="meta-side-value">{{ $slip['bulan'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">NAMA</td>
                    <td class="meta-colon">:</td>
                    <td class="meta-value">{{ $slip['nama'] ?? '-' }}</td>
                    <td class="meta-side-label">Unit Kerja :</td>
                    <td class="meta-side-value">{{ $slip['unit_kerja'] ?? '-' }}</td>
                </tr>
            </table>

            <table class="salary-table">
                <colgroup>
                    <col class="item-col">
                    <col class="jml-col">
                    <col class="rp-col">
                    <col class="amount-col">
                </colgroup>
                <tr>
                    <th>ITEM</th>
                    <th>JML</th>
                    <th colspan="2">JUMLAH (Rp)</th>
                </tr>

                <tr class="line-row">
                    <td class="item-text">{{ $slip['gaji_pokok']['label'] ?? 'GAJI POKOK' }}</td>
                    <td class="jml-col">{{ $formatJml($slip['gaji_pokok']['jml'] ?? null) }}</td>
                    <td class="rp-col">Rp</td>
                    <td class="amount-col">{{ $formatAmount($slip['gaji_pokok']['nominal'] ?? 0) }}</td>
                </tr>

                @foreach (($slip['tunjangan_rows'] ?? []) as $row)
                    <tr class="line-row{{ $rowMuted($row) }}">
                        <td class="item-text">{{ $row['label'] ?? '-' }}</td>
                        <td class="jml-col">{{ $formatJml($row['jml'] ?? null) }}</td>
                        <td class="rp-col">Rp</td>
                        <td class="amount-col">{{ $formatAmount($row['nominal'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="group-row">
                    <td>- JASA</td>
                    <td class="jml-col"></td>
                    <td class="rp-col"></td>
                    <td class="amount-col"></td>
                </tr>
                @foreach (($slip['jasa_rows'] ?? []) as $row)
                    <tr class="line-row{{ $rowMuted($row) }}">
                        <td class="item-text">- {{ $row['label'] ?? '-' }}</td>
                        <td class="jml-col">{{ $formatJml($row['jml'] ?? null) }}</td>
                        <td class="rp-col">Rp</td>
                        <td class="amount-col">{{ $formatAmount($row['nominal'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="group-row">
                    <td>JASA TINDAKAN</td>
                    <td class="jml-col"></td>
                    <td class="rp-col"></td>
                    <td class="amount-col"></td>
                </tr>
                @foreach (($slip['action_rows'] ?? []) as $row)
                    @php
                        $detailRows = $row['detail_rows'] ?? [];
                        $hasDetailRows = count($detailRows) > 0;
                    @endphp
                    <tr class="line-row{{ $rowMuted($row) }}">
                        <td class="item-text">- {{ $row['label'] ?? '-' }}</td>
                        <td class="jml-col">{{ $formatJml($row['jml'] ?? 0) }}</td>
                        <td class="rp-col">{{ $hasDetailRows ? '' : 'Rp' }}</td>
                        <td class="amount-col">{{ $hasDetailRows ? '' : $formatAmount($row['nominal'] ?? 0) }}</td>
                    </tr>
                    @foreach ($detailRows as $detailRow)
                        @php
                            $sourcePeriodText = $detailRow['source_period_text'] ?? null;
                        @endphp
                        <tr class="line-row detail-row{{ $rowMuted($detailRow) }}">
                            <td class="item-text detail-text">
                                - {{ $row['label'] ?? '-' }} {{ $detailRow['label'] ?? '-' }}
                                @if ($sourcePeriodText)
                                    <span class="source-text">Sumber BPJS: {{ $sourcePeriodText }}</span>
                                @endif
                            </td>
                            <td class="jml-col">{{ $formatJml($detailRow['jml'] ?? null) }}</td>
                            <td class="rp-col">Rp</td>
                            <td class="amount-col">{{ $formatAmount($detailRow['nominal'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                @endforeach

                @foreach (($slip['other_income_rows'] ?? []) as $row)
                    <tr class="line-row{{ $rowMuted($row) }}">
                        <td class="item-text">{{ $row['label'] ?? '-' }}</td>
                        <td class="jml-col">{{ $formatJml($row['jml'] ?? null) }}</td>
                        <td class="rp-col">Rp</td>
                        <td class="amount-col">{{ $formatAmount($row['nominal'] ?? 0) }}</td>
                    </tr>
                @endforeach

                @if (count($slip['bpjs_rows'] ?? []) > 0)
                    <tr class="group-row">
                        <td>{{ $slip['bpjs_title'] ?? 'BPJS' }}</td>
                        <td class="jml-col"></td>
                        <td class="rp-col"></td>
                        <td class="amount-col"></td>
                    </tr>
                    @foreach (($slip['bpjs_rows'] ?? []) as $row)
                        @php
                            $sourcePeriodText = $row['source_period_text'] ?? null;
                        @endphp
                        <tr class="line-row{{ $rowMuted($row) }}">
                            <td class="item-text">
                                {{ $row['label'] ?? '-' }}
                                @if ($sourcePeriodText)
                                    <span class="source-text">Sumber BPJS: {{ $sourcePeriodText }}</span>
                                @endif
                            </td>
                            <td class="jml-col">{{ $formatJml($row['jml'] ?? null) }}</td>
                            <td class="rp-col">Rp</td>
                            <td class="amount-col">{{ $formatAmount($row['nominal'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                @endif

                <tr class="total-row">
                    <td colspan="2" class="total-label">Total Pendapatan</td>
                    <td class="rp-col">Rp</td>
                    <td class="amount-col">{{ $formatAmount($slip['total_pendapatan'] ?? 0, false) }}</td>
                </tr>

                @foreach (($slip['deduction_rows'] ?? []) as $row)
                    <tr class="line-row{{ $rowMuted($row) }}">
                        <td colspan="2" class="item-text">{{ $row['label'] ?? '-' }}</td>
                        <td class="rp-col">Rp</td>
                        <td class="amount-col">{{ $formatAmount($row['nominal'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="2" class="total-label">Total Potongan</td>
                    <td class="rp-col">Rp</td>
                    <td class="amount-col">{{ $formatAmount($slip['total_potongan'] ?? 0, false) }}</td>
                </tr>
                <tr class="grand-row">
                    <td colspan="2">TOTAL GAJI BERSIH</td>
                    <td class="rp-col">Rp</td>
                    <td class="amount-col">{{ $formatAmount($slip['total_bersih'] ?? 0, false) }}</td>
                </tr>
                <tr class="grand-row">
                    <td colspan="2">DISIMPAN ARSY (15% dr GAJI BERSIH)</td>
                    <td class="rp-col">Rp</td>
                    <td class="amount-col">{{ $formatAmount($slip['disimpan_arsy'] ?? null) }}</td>
                </tr>
                <tr class="grand-row final-row">
                    <td colspan="2">TOTAL GAJI YANG DITERIMA</td>
                    <td class="rp-col">Rp</td>
                    <td class="amount-col">{{ $formatAmount($slip['total_diterima'] ?? 0, false) }}</td>
                </tr>
                <tr class="paid-source">
                    <td colspan="4">GAJI &nbsp;&nbsp; INI &nbsp;&nbsp; DIBAYAR &nbsp;&nbsp; OLEH &nbsp;&nbsp; PASIEN</td>
                </tr>
            </table>

            <table class="signature">
                <tr>
                    <td>
                        Dibayar oleh,
                        <span class="role">Bendahara</span>
                    </td>
                </tr>
            </table>
        </div>
    </body>

</html>
