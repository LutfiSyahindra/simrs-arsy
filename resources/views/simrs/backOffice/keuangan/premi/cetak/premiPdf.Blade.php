<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">

        <style>
            @page {
                margin: 24px 28px;
            }

            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 11px;
                color: #1f2937;
                line-height: 1.45;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .text-bold {
                font-weight: bold;
            }

            .text-muted {
                color: #6b7280;
            }

            .mt-20 {
                margin-top: 20px;
            }

            .header {
                border-bottom: 3px solid #2563eb;
                padding-bottom: 12px;
            }

            .header-table,
            .info-table,
            table.data,
            .footer-table {
                width: 100%;
                border-collapse: collapse;
            }

            .header-table td,
            .info-table td {
                border: none;
                vertical-align: middle;
            }

            .logo {
                width: 66px;
            }

            .rs-name {
                font-size: 18px;
                font-weight: bold;
                color: #0f172a;
                letter-spacing: .3px;
            }

            .report-title {
                display: inline-block;
                margin-top: 4px;
                padding: 4px 9px;
                background: #eff6ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
                font-size: 12px;
                font-weight: bold;
            }

            .info-box {
                margin-top: 18px;
                border: 1px solid #dbe3ef;
                background: #f8fafc;
                padding: 10px;
            }

            .info-label {
                color: #64748b;
                font-size: 10px;
            }

            .info-value {
                font-weight: bold;
                color: #111827;
            }

            .section-title {
                margin-top: 20px;
                padding: 7px 9px;
                background: #eef2ff;
                color: #1e3a8a;
                border-left: 5px solid #2563eb;
                font-weight: bold;
                text-transform: uppercase;
            }

            table.data {
                margin-top: 8px;
                border: 1px solid #dbe3ef;
            }

            table.data th {
                background: #1e40af;
                color: #fff;
                padding: 7px 5px;
                font-size: 10px;
                border: 1px solid #1e3a8a;
            }

            table.data td {
                padding: 6px 5px;
                border: 1px solid #e5e7eb;
                vertical-align: top;
            }

            table.data tbody tr:nth-child(even) td {
                background: #f8fafc;
            }

            .subtotal td {
                background: #eff6ff !important;
                font-weight: bold;
                color: #1e3a8a;
                border-top: 2px solid #93c5fd !important;
            }

            .grand-total {
                margin-top: 24px;
                padding: 12px;
                border: 2px solid #2563eb;
                background: #eff6ff;
                color: #0f172a;
                font-size: 14px;
                font-weight: bold;
            }

            .footer {
                margin-top: 42px;
            }

            .signature-line {
                margin-top: 42px;
                border-top: 1px solid #111827;
                display: inline-block;
                min-width: 190px;
                padding-top: 5px;
            }

            .nowrap {
                white-space: nowrap;
            }
        </style>
    </head>

    <body>

        <!-- ================= HEADER ================= -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td width="15%" class="text-center">
                        <img src="{{ public_path("plugins/img/logoarsy.png") }}" alt="Logo RS" class="logo">
                    </td>
                    <td width="85%">
                        <div class="rs-name">RS ABDURRAHMAN SYAMSYURI</div>
                        <div class="report-title">LAPORAN DETAIL PREMI</div>
                        <div class="text-muted">
                            Periode {{ $tgl_awal }} s/d {{ $tgl_akhir }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ================= INFO ================= -->
        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td width="12%" class="info-label">Nama</td>
                    <td width="2%">:</td>
                    <td width="36%" class="info-value">
                        {{ $nama }}
                        @if (!empty($penjamin))
                            ({{ strtoupper($penjamin) }})
                        @endif
                    </td>

                    <td width="12%" class="info-label">Jenis</td>
                    <td width="2%">:</td>
                    <td width="36%" class="info-value">{{ strtoupper($JenisParamedis) }}</td>
                </tr>
                <tr>
                    <td class="info-label">Status</td>
                    <td>:</td>
                    <td>{{ $status ?? "-" }}</td>

                    <td class="info-label">Tanggal Cetak</td>
                    <td>:</td>
                    <td>{{ now()->format("d-m-Y H:i") }}</td>
                </tr>
            </table>
        </div>

        @php
            $grandTotal = 0;
        @endphp

        <!-- ================= DATA ================= -->
        @if ($jenis === "Rumah Sakit")
            {{-- ================= MODE RUMAH SAKIT ================= --}}
            @php $grandTotal = 0; @endphp

            @foreach ($data->groupBy("sumber") as $sumber => $rows)
                @php
                    $subTotal = $rows->sum("nilai");
                    $grandTotal += $subTotal;
                @endphp

                <div class="section-title">
                    {{ strtoupper(str_replace("_", " ", $sumber)) }}
                </div>

                <table class="data">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>No Rawat</th>
                            <th>Sumber</th>
                            <th>Tindakan</th>
                            <th class="text-right">Premi RS (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->tanggal)->format("d-m-Y") }}</td>
                                <td>{{ $row->no_rawat ?? "-" }}</td>
                                <td>{{ $row->sumber ?? "-" }}</td>
                                <td>{{ $row->tindakan ?? "-" }}</td>
                                <td class="text-right">{{ number_format($row->nilai, 0, ",", ".") }}</td>
                            </tr>
                        @endforeach

                        <tr class="subtotal">
                            <td colspan="5" class="text-right">Subtotal {{ strtoupper($sumber) }}</td>
                            <td class="text-right">{{ number_format($subTotal, 0, ",", ".") }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @elseif ($jenis !== "Kamar")
            {{-- ================= MODE UMUM ================= --}}
            @php $grandTotal = 0; @endphp

            @foreach ($data->groupBy("layanan") as $layanan => $rows)
                @php
                    $subTotal = $rows->sum("nilai");
                    $grandTotal += $subTotal;
                @endphp

                <div class="section-title">
                    {{ strtoupper($layanan) }}
                </div>

                <table class="data">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>No Rawat</th>
                            <th>Tindakan</th>
                            <th class="text-right">Premi (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->tanggal)->format("d-m-Y") }}</td>
                                <td>{{ $row->no_rawat }}</td>
                                <td>{{ $row->tindakan }}</td>
                                <td class="text-right">{{ number_format($row->nilai, 0, ",", ".") }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @else
            {{-- ================= MODE KAMAR ================= --}}
            @php
                $grandTotal = $data->sum("nilai");
            @endphp

            <table class="data">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tgl Masuk</th>
                        <th>Tgl Keluar</th>
                        <th>No Rawat</th>
                        <th>Lama</th>
                        <th class="text-right">Premi (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $i => $row)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="text-center">{{ \Carbon\Carbon::parse($row->tanggal)->format("d-m-Y") }}
                            </td>
                            <td class="text-center">
                                {{ $row->tgl_keluar ?? "-" }}
                            </td>
                            <td class="text-center">{{ $row->no_rawat }}</td>
                            <td class="text-center">{{ $row->lama }}</td>
                            <td class="text-right">{{ number_format($row->nilai, 0, ",", ".") }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- ================= GRAND TOTAL ================= -->
        <div class="grand-total text-right">
            GRAND TOTAL PREMI :
            Rp {{ number_format($grandTotal, 0, ",", ".") }}
        </div>

        <!-- ================= FOOTER ================= -->
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td width="60%"></td>
                    <td width="40%" class="text-center">
                        Lamongan, {{ now()->format("d F Y") }}<br>
                        <strong>Mengetahui</strong><br>
                        <span class="signature-line">Nama & Tanda Tangan</span>
                    </td>
                </tr>
            </table>
        </div>

    </body>

</html>
