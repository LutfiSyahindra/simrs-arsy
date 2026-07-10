@php
    use Carbon\Carbon;

    $periode = $data["periode"] ?? null;
    $bulanSlip = $periode ? strtoupper(Carbon::createFromFormat("Y-m", $periode)->translatedFormat("F Y")) : "-";
    $tunjanganDetail = collect($data["tunjangan_detail"] ?? []);
    $totalTahap1 = (int) ($data["total"] ?? 0);
    $komponenGajiLabel = strtoupper($data["komponen_gaji_label"] ?? "Gaji Pokok");
    $stage2 = $data["tahap2"] ?? null;
    $hasStage2 = is_array($stage2) && !empty($stage2);
    $stage2PremiDetail = collect($stage2["premi_detail"] ?? []);
    $stage2SlipPendapatanUmum = $stage2["slip_pendapatan_umum"] ?? [];
    $stage2JasaTindakan = $stage2SlipPendapatanUmum["jasa_tindakan"] ?? [];
    $stage2JasaTindakanItems = collect($stage2JasaTindakan["items"] ?? []);
    $stage2JasaTindakanTotal = (int) ($stage2JasaTindakan["total"] ?? $stage2JasaTindakanItems->sum("nominal"));
    $stage2PremiBersama = (int) (($stage2SlipPendapatanUmum["premi_bersama"]["nominal"] ?? 0));
    $stage2HasGroupedPremi = $stage2JasaTindakanTotal > 0 || $stage2PremiBersama > 0;
    $stage2PotonganDetail = collect($stage2["potongan_detail"] ?? []);
    $stage2GajiDibayar = (int) ($stage2["gaji_dibayar"] ?? 0);
    $stage2SalaryLabel = strtoupper($stage2["komponen_gaji_dibayar_label"] ?? ($data["komponen_gaji_dibayar_label"] ?? "Gaji Dibayarkan"));
    $totalTahap2Bruto = (int) ($stage2["total_bruto"] ?? ($stage2GajiDibayar + (int) ($stage2["total_premi"] ?? 0)));
    $totalTahap2Potongan = (int) ($stage2["total_potongan"] ?? 0);
    $totalTahap2 = (int) ($stage2["total"] ?? 0);

    $rupiahSlip = static fn($angka) => number_format((int) $angka, 0, ",", ".");
    $rupiahStage2 = static fn($angka) => $hasStage2 ? number_format((int) $angka, 0, ",", ".") : "-";
@endphp

<!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <style>
            @page {
                size: A4 portrait;
                margin: 12mm 8mm;
            }

            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 8.5px;
                color: #000;
            }

            .sheet {
                width: 100%;
                display: table;
                table-layout: fixed;
            }

            .slip-column {
                display: table-cell;
                width: 50%;
                vertical-align: top;
                padding: 0 5mm;
            }

            .slip-column:first-child {
                border-right: 1px dashed #999;
            }

            .copy-label {
                text-align: right;
                font-size: 8px;
                font-weight: bold;
                margin-bottom: 2px;
            }

            .header {
                text-align: center;
                position: relative;
                margin-bottom: 5px;
                min-height: 42px;
            }

            .logo {
                position: absolute;
                left: 2px;
                top: 0;
                width: 34px;
            }

            .hospital {
                font-size: 8.5px;
                line-height: 1.2;
                padding-left: 34px;
            }

            .title {
                margin-top: 4px;
                font-size: 11px;
                font-weight: bold;
                text-align: center;
            }

            .title span {
                text-decoration: underline;
            }

            .identity {
                width: 100%;
                margin-top: 4px;
                margin-bottom: 3px;
                border-collapse: collapse;
            }

            .identity td {
                padding: .5px 0;
                vertical-align: top;
            }

            .label {
                width: 50px;
            }

            .colon {
                width: 6px;
            }

            .section-title {
                font-weight: bold;
                margin-top: 3px;
            }

            .salary-table {
                width: 100%;
                border-collapse: collapse;
            }

            .salary-table td {
                padding: .5px 0;
                vertical-align: top;
            }

            .col-letter {
                width: 20px;
                text-align: right;
                padding-right: 5px !important;
                font-weight: bold;
            }

            .col-no {
                width: 15px;
                text-align: right;
                padding-right: 4px !important;
            }

            .col-name {
                width: auto;
            }

            .income-subitem {
                padding-left: 8px !important;
            }

            .col-rp {
                width: 21px;
                font-weight: bold;
                white-space: nowrap;
            }

            .col-value {
                width: 58px;
                text-align: right;
                white-space: nowrap;
            }

            .bold {
                font-weight: bold;
            }

            .center {
                text-align: center;
            }

            .summary td {
                font-weight: bold;
            }

            .signature {
                width: 100%;
                margin-top: 10px;
                border-collapse: collapse;
                font-size: 6.5px;
            }

            .signature td {
                width: 50%;
                text-align: center;
                vertical-align: top;
            }

            .signature .line {
                display: inline-block;
                min-width: 28px;
                border-bottom: 1px solid #000;
                color: #2563eb;
                font-weight: bold;
            }
        </style>
    </head>

    <body>
        <div class="sheet">
            @foreach (["PEGAWAI", "ARSIP"] as $copy)
                <div class="slip-column">
                    <div class="copy-label">{{ $copy }}</div>

                    <div class="header">
                        <img class="logo" src="{{ public_path("plugins/img/logoarsy.png") }}" alt="Logo">

                        <div class="hospital">
                            RUMAH SAKIT ABDURRAHMAN SYAMSURI<br>
                            (RS. ARSY)<br>
                            PACIRAN LAMONGAN
                        </div>

                        <div class="title">
                            SLIP GAJI <span>BULAN : {{ $bulanSlip }}</span>
                        </div>
                    </div>

                    <table class="identity">
                        <tr>
                            <td class="label">NAMA</td>
                            <td class="colon">:</td>
                            <td>{{ $data["nama"] ?? "-" }}</td>
                        </tr>
                        <tr>
                            <td>UNIT KERJA</td>
                            <td>:</td>
                            <td>{{ $data["unit_kerja"] ?? ($data["jabatan"] ?? "-") }}</td>
                        </tr>
                        <tr>
                            <td>STATUS</td>
                            <td>:</td>
                            <td>{{ $data["status_label"] ?? "-" }}</td>
                        </tr>
                        <tr>
                            <td>JABATAN</td>
                            <td>:</td>
                            <td>{{ $data["jabatan"] ?? "-" }}</td>
                        </tr>
                    </table>

                    <div class="section-title">GAJI TAHAP 1</div>

                    <table class="salary-table">
                        <tr>
                            <td class="col-letter">A.</td>
                            <td colspan="4" class="bold">PENDAPATAN UMUM</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="col-no">1.</td>
                            <td class="col-name">{{ $komponenGajiLabel }}</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahSlip($data["gaji_dibayar"] ?? 0) }}</td>
                        </tr>

                        @foreach ($tunjanganDetail as $index => $tunjangan)
                            <tr>
                                <td></td>
                                <td class="col-no">{{ $index + 2 }}.</td>
                                <td class="col-name">{{ strtoupper($tunjangan["nama"] ?? "TUNJANGAN") }}</td>
                                <td class="col-rp">: Rp.</td>
                                <td class="col-value">{{ $rupiahSlip($tunjangan["nominal"] ?? 0) }}</td>
                            </tr>
                        @endforeach

                        <tr class="summary">
                            <td></td>
                            <td></td>
                            <td class="col-name center">GAJI TAHAP 1</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahSlip($totalTahap1) }}</td>
                        </tr>
                        <tr class="summary">
                            <td></td>
                            <td></td>
                            <td class="col-name center">DITERIMA / DI TRANSFER</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahSlip($totalTahap1) }}</td>
                        </tr>
                    </table>

                    <div class="section-title">GAJI TAHAP 2</div>

                    <table class="salary-table">
                        <tr>
                            <td class="col-letter">A.</td>
                            <td colspan="4" class="bold">PENDAPATAN UMUM</td>
                        </tr>

                        @php $stage2IncomeNo = 1; @endphp

                        @if ($hasStage2 && $stage2GajiDibayar > 0)
                            <tr>
                                <td></td>
                                <td class="col-no">{{ $stage2IncomeNo++ }}.</td>
                                <td class="col-name">{{ $stage2SalaryLabel }}</td>
                                <td class="col-rp">: Rp.</td>
                                <td class="col-value">{{ $rupiahSlip($stage2GajiDibayar) }}</td>
                            </tr>
                        @endif

                        @if ($stage2HasGroupedPremi)
                            <tr>
                                <td></td>
                                <td class="col-no">{{ $stage2IncomeNo++ }}.</td>
                                <td class="col-name bold">JASA TINDAKAN</td>
                                <td></td>
                                <td></td>
                            </tr>

                            @foreach ($stage2JasaTindakanItems as $index => $item)
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="col-name income-subitem">{{ chr(97 + $index) }}. {{ strtoupper($item["label"] ?? "-") }}</td>
                                    <td class="col-rp">: Rp.</td>
                                    <td class="col-value">{{ $rupiahSlip($item["nominal"] ?? 0) }}</td>
                                </tr>
                            @endforeach

                            <tr>
                                <td></td>
                                <td class="col-no">{{ $stage2IncomeNo++ }}.</td>
                                <td class="col-name">PREMI BERSAMA</td>
                                <td class="col-rp">: Rp.</td>
                                <td class="col-value">{{ $rupiahSlip($stage2PremiBersama) }}</td>
                            </tr>
                        @else
                            @foreach ($stage2PremiDetail as $premi)
                                <tr>
                                    <td></td>
                                    <td class="col-no">{{ $stage2IncomeNo++ }}.</td>
                                    <td class="col-name">{{ strtoupper($premi["nama"] ?? "PREMI") }}</td>
                                    <td class="col-rp">: Rp.</td>
                                    <td class="col-value">{{ $rupiahSlip($premi["nominal"] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        @endif

                        @if (! $hasStage2 || ($stage2GajiDibayar <= 0 && ! $stage2HasGroupedPremi && $stage2PremiDetail->isEmpty()))
                            <tr>
                                <td></td>
                                <td class="col-no">1.</td>
                                <td class="col-name">BELUM ADA DATA TAHAP 2</td>
                                <td class="col-rp">: Rp.</td>
                                <td class="col-value">-</td>
                            </tr>
                        @endif

                        <tr class="summary">
                            <td></td>
                            <td></td>
                            <td class="col-name center">TOTAL PENDAPATAN</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahStage2($totalTahap2Bruto) }}</td>
                        </tr>

                        <tr>
                            <td class="col-letter">B.</td>
                            <td colspan="4" class="bold">POTONGAN</td>
                        </tr>

                        @forelse ($stage2PotonganDetail as $i => $potongan)
                            <tr>
                                <td></td>
                                <td class="col-no">{{ $i + 1 }}.</td>
                                <td class="col-name">{{ strtoupper($potongan["nama"] ?? "POTONGAN") }}</td>
                                <td class="col-rp">: Rp.</td>
                                <td class="col-value">{{ $rupiahSlip($potongan["nominal"] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td></td>
                                <td class="col-no">1.</td>
                                <td class="col-name">{{ $hasStage2 ? "TIDAK ADA POTONGAN" : "BELUM ADA DATA TAHAP 2" }}</td>
                                <td class="col-rp">: Rp.</td>
                                <td class="col-value">-</td>
                            </tr>
                        @endforelse

                        <tr class="summary">
                            <td></td>
                            <td></td>
                            <td class="col-name center">TOTAL POTONGAN</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahStage2($totalTahap2Potongan) }}</td>
                        </tr>
                        <tr class="summary">
                            <td></td>
                            <td></td>
                            <td class="col-name center">GAJI TAHAP 2</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahStage2($totalTahap2) }}</td>
                        </tr>
                        <tr class="summary">
                            <td></td>
                            <td></td>
                            <td class="col-name center">DI TERIMA / DI TRANSFER</td>
                            <td class="col-rp">: Rp.</td>
                            <td class="col-value">{{ $rupiahStage2($totalTahap2) }}</td>
                        </tr>
                    </table>

                    <table class="signature">
                        <tr>
                            <td>
                                DIBAYARKAN <span class="line">OLEH :</span><br>
                                KABAG KEUANGAN
                            </td>
                            <td>
                                DITERIMA <span class="line">OLEH :</span>
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        </div>
    </body>

</html>
