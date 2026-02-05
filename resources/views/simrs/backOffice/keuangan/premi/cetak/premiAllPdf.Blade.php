<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">

        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 11px;
                color: #222;
                line-height: 1.4;
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
                color: #666;
            }

            .mt-10 {
                margin-top: 10px;
            }

            .mt-20 {
                margin-top: 20px;
            }

            .mt-30 {
                margin-top: 30px;
            }

            /* HEADER */
            .header {
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }

            .header-table {
                width: 100%;
                border-collapse: collapse;
            }

            .header-table td {
                border: none;
                vertical-align: middle;
            }

            .rs-name {
                font-size: 18px;
                font-weight: bold;
            }

            .report-title {
                font-size: 13px;
                font-weight: bold;
            }

            /* INFO */
            .info-box {
                border: 1px solid #ccc;
                padding: 8px;
            }

            .info-table {
                width: 100%;
                border-collapse: collapse;
            }

            .info-table td {
                border: none;
                padding: 3px 4px;
            }

            /* DATA */
            table.data {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
            }

            table.data th {
                border-bottom: 1px solid #000;
                padding: 6px 4px;
                font-weight: bold;
            }

            table.data td {
                border-bottom: 1px solid #e0e0e0;
                padding: 5px 4px;
            }

            .section-title {
                background: #f0f0f0;
                border-left: 5px solid #000;
                padding: 6px 8px;
                font-weight: bold;
                margin-top: 25px;
            }

            .subtotal {
                font-weight: bold;
                border-top: 1px solid #000;
                background: #fafafa;
            }

            .grand-total {
                margin-top: 30px;
                padding: 12px;
                border: 2px solid #000;
                font-size: 14px;
                font-weight: bold;
            }

            .footer {
                margin-top: 50px;
            }

            .logo {
                width: 65px;
                opacity: 0.95;
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
                        <div class="report-title">LAPORAN PREMI</div>
                        <div class="text-muted">
                            Periode {{ $tgl_awal }} s/d {{ $tgl_akhir }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ================= INFO ================= -->
        <div class="info-box mt-20">
            <table class="info-table">
                <tr>
                    <td width="10%">Premi</td>
                    <td width="2%">:</td>
                    <td width="28%" class="text-bold">{{ strtoupper($jenis) }}</td>

                    <td width="10%">Penjamin</td>
                    <td width="2%">:</td>
                    <td width="28%"> {{ strtoupper($penjamin ?? 'Semua Penjamin') }}</td>

                </tr>
            </table>
        </div>

        @php
            $grandTotal = 0;
        @endphp

        {{-- ================= MODE KAMAR ================= --}}
        @php
            $grandTotal = $data->sum("premi");
        @endphp

        <table class="data">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th class="text-right">Premi (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-center">{{ $row['nama'] }}</td>
                        <td class="text-right">{{ number_format($row['premi'], 0, ",", ".") }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- ================= GRAND TOTAL ================= -->
        <div class="grand-total text-right">
            GRAND TOTAL PREMI :
            Rp {{ number_format($grandTotal, 0, ",", ".") }}
        </div>

        <!-- ================= FOOTER ================= -->
        <div class="footer">
            <table width="100%">
                <tr>
                    <td width="60%"></td>
                    <td width="40%" class="text-center">
                        Lamongan, {{ now()->format("d F Y") }}<br><br>
                        <strong>Mengetahui</strong><br><br><br>
                        <strong>__________________________</strong>
                    </td>
                </tr>
            </table>
        </div>

    </body>

</html>
