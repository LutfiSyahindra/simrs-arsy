@extends("template.partials.app")

@push("style")
    @include("template.AddOn.mdiicon")
    <style>
        body {
            background: #f5f7fb;
        }

        .finance-dashboard {
            color: #0f172a;
        }

        .finance-toolbar,
        .finance-panel,
        .finance-metric,
        .finance-insight {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
        }

        .finance-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 420px);
            align-items: stretch;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }

        .finance-toolbar::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #16a34a 45%, #f59e0b 75%, #dc2626);
        }

        .finance-header-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: .95rem;
        }

        .finance-header-kicker {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .45rem;
            color: #475569;
            font-size: .73rem;
            font-weight: 850;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .finance-header-status {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-height: 26px;
            padding: .18rem .55rem;
            color: #047857;
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
        }

        .finance-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .14);
        }

        .finance-header-period {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-height: 26px;
            padding: .18rem .55rem;
            color: #1d4ed8;
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
        }

        .finance-title {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 850;
            line-height: 1.18;
            letter-spacing: 0;
        }

        .finance-subtitle {
            margin: .35rem 0 0;
            color: #64748b;
            font-size: .86rem;
            line-height: 1.45;
            max-width: 760px;
        }

        .finance-header-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
        }

        .finance-header-stat {
            min-width: 0;
            padding: .75rem;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .finance-header-stat-label {
            color: #64748b;
            font-size: .7rem;
            font-weight: 850;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .finance-header-stat-value {
            margin-top: .22rem;
            color: #0f172a;
            font-size: .95rem;
            font-weight: 850;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .finance-header-stat-note {
            margin-top: .15rem;
            color: #64748b;
            font-size: .72rem;
            line-height: 1.25;
        }

        .finance-header-aside {
            display: grid;
            gap: .75rem;
            align-content: start;
            padding: .85rem;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .finance-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .5rem;
        }

        .finance-period-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
            gap: .45rem;
        }

        .finance-period-field {
            min-width: 0;
        }

        .finance-period-label {
            display: block;
            color: #64748b;
            font-size: .7rem;
            font-weight: 850;
            letter-spacing: .03em;
            text-transform: uppercase;
            margin-bottom: .28rem;
        }

        .finance-period-form .form-control {
            width: 100%;
            min-height: 38px;
            border-color: #dbe2ea;
            border-radius: 8px;
        }

        .finance-soft-link,
        .finance-period-form .btn {
            min-height: 40px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .finance-soft-link {
            flex-direction: column;
            align-items: flex-start;
            gap: .1rem;
            padding: .55rem .65rem;
            text-align: left;
            white-space: normal;
            line-height: 1.15;
        }

        .finance-soft-link span {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .finance-soft-link small {
            color: #64748b;
            font-size: .67rem;
            font-weight: 700;
        }

        .finance-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .finance-metric {
            padding: 1rem;
            min-width: 0;
            overflow: hidden;
        }

        .finance-metric-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .finance-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 42px;
            font-size: 1.25rem;
        }

        .finance-icon.primary {
            color: #1d4ed8;
            background: #dbeafe;
        }

        .finance-icon.success {
            color: #047857;
            background: #d1fae5;
        }

        .finance-icon.warning {
            color: #b45309;
            background: #fef3c7;
        }

        .finance-icon.danger {
            color: #be123c;
            background: #ffe4e6;
        }

        .finance-icon.info {
            color: #0e7490;
            background: #cffafe;
        }

        .finance-icon.muted {
            color: #64748b;
            background: #f1f5f9;
        }

        .finance-label {
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .finance-value {
            margin: .45rem 0 .15rem;
            font-size: 1.22rem;
            font-weight: 850;
            line-height: 1.25;
            word-break: break-word;
        }

        .finance-note {
            color: #64748b;
            font-size: .78rem;
            line-height: 1.35;
        }

        .finance-note strong {
            color: #0f172a;
        }

        .finance-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, .9fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .finance-grid-balanced {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .finance-panel {
            min-width: 0;
            padding: 1rem;
        }

        .finance-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .finance-panel-title {
            margin: 0;
            font-size: .96rem;
            font-weight: 850;
        }

        .finance-panel-subtitle {
            margin: .15rem 0 0;
            color: #64748b;
            font-size: .78rem;
        }

        .finance-chart {
            min-height: 310px;
        }

        .finance-chart-sm {
            min-height: 255px;
        }

        .finance-empty {
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-weight: 700;
            text-align: center;
        }

        .finance-payroll-overview {
            display: grid;
            gap: .85rem;
        }

        .finance-payroll-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .85rem;
            align-items: center;
            padding: .85rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }

        .finance-payroll-head-value {
            margin-top: .25rem;
            font-size: 1.18rem;
            font-weight: 850;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .finance-payroll-head-meta {
            color: #64748b;
            font-size: .77rem;
            margin-top: .2rem;
        }

        .finance-payroll-split {
            width: min(210px, 100%);
        }

        .finance-payroll-split-track {
            display: flex;
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .finance-payroll-split-fill {
            width: var(--split, 0%);
            min-width: 0;
        }

        .finance-payroll-split-fill.stage-one {
            background: #2563eb;
        }

        .finance-payroll-split-fill.stage-two {
            background: #16a34a;
        }

        .finance-payroll-split-legend {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            color: #64748b;
            font-size: .7rem;
            font-weight: 800;
            margin-top: .4rem;
        }

        .finance-stage-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .finance-stage {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: .9rem;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .finance-stage::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--stage-accent, #2563eb);
        }

        .finance-stage.stage-one {
            --stage-accent: #2563eb;
        }

        .finance-stage.stage-two {
            --stage-accent: #16a34a;
        }

        .finance-stage-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
            color: #334155;
            margin-bottom: .75rem;
        }

        .finance-stage-title-main {
            display: flex;
            align-items: center;
            gap: .55rem;
            min-width: 0;
        }

        .finance-stage-title-main i {
            color: var(--stage-accent, #2563eb);
            font-size: 1.25rem;
        }

        .finance-stage-name {
            color: #0f172a;
            font-size: .9rem;
            font-weight: 850;
            line-height: 1.2;
        }

        .finance-stage-caption {
            color: #64748b;
            font-size: .72rem;
            line-height: 1.2;
            margin-top: .15rem;
        }

        .finance-stage-share {
            color: var(--stage-accent, #2563eb);
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: .22rem .5rem;
            font-size: .68rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .finance-stage-total {
            font-size: 1.14rem;
            font-weight: 850;
            line-height: 1.2;
            margin-bottom: .65rem;
            overflow-wrap: anywhere;
        }

        .finance-stage-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .45rem;
            margin-bottom: .75rem;
        }

        .finance-stage-meta span {
            min-width: 0;
            padding: .5rem;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 8px;
            font-size: .7rem;
            line-height: 1.25;
        }

        .finance-stage-meta strong {
            display: block;
            color: #0f172a;
            font-size: .82rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .finance-stage-breakdown {
            display: grid;
            gap: .55rem;
        }

        .finance-stage-line {
            display: grid;
            gap: .25rem;
        }

        .finance-stage-line-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            color: #64748b;
            font-size: .73rem;
        }

        .finance-stage-line-head strong {
            color: #0f172a;
            font-size: .76rem;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .finance-stage-progress {
            height: 7px;
            overflow: hidden;
            border-radius: 999px;
            background: #eef2f7;
        }

        .finance-stage-progress-fill {
            width: var(--bar, 0%);
            height: 100%;
            border-radius: inherit;
            background: var(--stage-accent, #2563eb);
        }

        .finance-stage-workforce {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .45rem;
            margin-top: .75rem;
            padding-top: .75rem;
            border-top: 1px solid #eef2f7;
        }

        .finance-stage-workforce span {
            color: #64748b;
            font-size: .7rem;
            line-height: 1.25;
        }

        .finance-stage-workforce strong {
            display: block;
            color: #0f172a;
            font-size: .86rem;
        }

        .finance-source-list,
        .finance-generator-list,
        .finance-recipient-list {
            display: grid;
            gap: .7rem;
        }

        .finance-source-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .7rem;
            align-items: center;
        }

        .finance-source-name,
        .finance-recipient-name,
        .finance-generator-name {
            font-weight: 800;
            color: #0f172a;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .finance-source-meta,
        .finance-recipient-meta,
        .finance-generator-meta {
            color: #64748b;
            font-size: .76rem;
            margin-top: .12rem;
        }

        .finance-source-total {
            font-weight: 850;
            white-space: nowrap;
        }

        .finance-bar {
            position: relative;
            height: 8px;
            background: #eef2f7;
            border-radius: 999px;
            overflow: hidden;
            margin-top: .45rem;
        }

        .finance-bar-fill {
            width: var(--bar, 0%);
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2563eb, #16a34a);
        }

        .finance-recipient-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .8rem;
            align-items: center;
            padding-bottom: .7rem;
            border-bottom: 1px solid #eef2f7;
        }

        .finance-recipient-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .finance-recipient-total {
            font-weight: 850;
            color: #047857;
            white-space: nowrap;
        }

        .finance-generator-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .finance-generator-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: .85rem;
            min-width: 0;
            background: #ffffff;
        }

        .finance-generator-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .65rem;
            margin-bottom: .65rem;
        }

        .finance-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .finance-badge.success {
            color: #047857;
            background: #d1fae5;
        }

        .finance-badge.warning {
            color: #b45309;
            background: #fef3c7;
        }

        .finance-badge.muted {
            color: #64748b;
            background: #f1f5f9;
        }

        .finance-generator-number {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            color: #64748b;
            font-size: .76rem;
            margin-top: .3rem;
        }

        .finance-generator-number strong {
            color: #0f172a;
        }

        .finance-insights {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .finance-insight {
            padding: .95rem;
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: .75rem;
            align-items: flex-start;
        }

        .finance-insight-value {
            font-weight: 850;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .finance-insight-text {
            color: #64748b;
            font-size: .76rem;
            line-height: 1.35;
            margin-top: .2rem;
        }

        @media (max-width: 1399.98px) {
            .finance-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .finance-generator-grid,
            .finance-insights {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .finance-toolbar,
            .finance-panel-header {
                align-items: stretch;
            }

            .finance-toolbar {
                grid-template-columns: 1fr;
            }

            .finance-actions {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .finance-grid,
            .finance-grid-balanced {
                grid-template-columns: 1fr;
            }

            .finance-payroll-head {
                grid-template-columns: 1fr;
            }

            .finance-payroll-split {
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .finance-metrics,
            .finance-header-stats,
            .finance-stage-grid,
            .finance-generator-grid,
            .finance-insights {
                grid-template-columns: 1fr;
            }

            .finance-actions,
            .finance-period-form {
                grid-template-columns: 1fr;
                width: 100%;
            }

            .finance-period-form .form-control,
            .finance-period-form .btn,
            .finance-soft-link {
                width: 100%;
            }

            .finance-period-form {
                flex-wrap: wrap;
            }

            .finance-source-row,
            .finance-recipient-row {
                grid-template-columns: 1fr;
            }

            .finance-source-total,
            .finance-recipient-total {
                white-space: normal;
            }
        }
    </style>
@endpush

@section("content")
    @php
        $rupiah = fn ($value) => "Rp " . number_format((int) $value, 0, ",", ".");
        $angka = fn ($value) => number_format((int) $value, 0, ",", ".");
        $persen = fn ($value, $total) => $total > 0 ? round(((int) $value / (int) $total) * 100, 1) : 0;
        $deltaPayroll = (int) $summary["total_payroll"] - (int) $previousSummary["total_payroll"];
        $deltaPayrollPercent = (int) $previousSummary["total_payroll"] > 0
            ? round(($deltaPayroll / (int) $previousSummary["total_payroll"]) * 100, 1)
            : ((int) $summary["total_payroll"] > 0 ? 100 : 0);
        $deltaPayrollLabel = ($deltaPayroll > 0 ? "+" : "") . $deltaPayrollPercent . "%";
        $maxSource = max(1, (int) $premium["sources"]->max("total"));
        $maxGenerator = max(1, (int) $generator["top"]->max("amount"));
    @endphp

    <div class="finance-dashboard">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("dashboard") }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Keuangan</li>
            </ol>
        </nav>

        <section class="finance-toolbar">
            <div class="finance-header-main">
                <div>
                    <div class="finance-header-kicker">
                        <span class="finance-header-status">
                            <span class="finance-status-dot"></span>
                            Modul Keuangan
                        </span>
                        <span class="finance-header-period">
                            <i class="mdi mdi-calendar-month-outline"></i>
                            {{ $periodeLabel }}
                        </span>
                    </div>
                    <h1 class="finance-title">Dashboard Keuangan</h1>
                    <p class="finance-subtitle">Analitik penggajian, premi, tindakan, dan generator dalam satu ruang kendali periode berjalan.</p>
                </div>

                <div class="finance-header-stats">
                    <div class="finance-header-stat">
                        <div class="finance-header-stat-label">Payroll Netto</div>
                        <div class="finance-header-stat-value">{{ $rupiah($summary["total_payroll"]) }}</div>
                        <div class="finance-header-stat-note">{{ $deltaPayrollLabel }} dari {{ $previousPeriodLabel }}</div>
                    </div>
                    <div class="finance-header-stat">
                        <div class="finance-header-stat-label">Premi</div>
                        <div class="finance-header-stat-value">{{ $rupiah($summary["total_premi"]) }}</div>
                        <div class="finance-header-stat-note">{{ $persen($summary["total_premi"], max(1, $summary["total_payroll"])) }}% dari payroll</div>
                    </div>
                    <div class="finance-header-stat">
                        <div class="finance-header-stat-label">Generator</div>
                        <div class="finance-header-stat-value">{{ $generator["summary"]["locked_rate"] }}% locked</div>
                        <div class="finance-header-stat-note">{{ $angka($generator["summary"]["generated_modules"]) }} modul aktif</div>
                    </div>
                </div>
            </div>

            <aside class="finance-header-aside">
                <form method="GET" action="{{ route("backOffice.keuangan.dashboard") }}" class="finance-period-form">
                    <div class="finance-period-field">
                        <label class="finance-period-label" for="financeDashboardPeriod">Periode Analitik</label>
                        <input type="month" id="financeDashboardPeriod" name="periode" class="form-control" value="{{ $periode }}" aria-label="Periode">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-filter-variant"></i>
                        Terapkan
                    </button>
                </form>

                <div class="finance-actions">
                    <a href="{{ route("backOffice.keuangan.premi") }}" class="btn btn-outline-success finance-soft-link">
                        <span><i class="mdi mdi-stethoscope"></i> Tindakan</span>
                        <small>Premi tindakan</small>
                    </a>
                    <a href="{{ route("backOffice.keuangan.penggajian") }}" class="btn btn-outline-primary finance-soft-link">
                        <span><i class="mdi mdi-account-cash-outline"></i> Penggajian</span>
                        <small>Slip dan tahap</small>
                    </a>
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}" class="btn btn-outline-warning finance-soft-link">
                        <span><i class="mdi mdi-calculator-variant-outline"></i> Hitung Premi</span>
                        <small>Generator</small>
                    </a>
                </div>
            </aside>
        </section>

        <section class="finance-metrics">
            <div class="finance-metric">
                <div class="finance-metric-top">
                    <div>
                        <div class="finance-label">Payroll Netto</div>
                        <div class="finance-value">{{ $rupiah($summary["total_payroll"]) }}</div>
                        <div class="finance-note">
                            <strong>{{ $deltaPayrollLabel }}</strong> dari {{ $previousPeriodLabel }}
                        </div>
                    </div>
                    <span class="finance-icon primary"><i class="mdi mdi-cash-multiple"></i></span>
                </div>
            </div>

            <div class="finance-metric">
                <div class="finance-metric-top">
                    <div>
                        <div class="finance-label">Total Premi</div>
                        <div class="finance-value">{{ $rupiah($summary["total_premi"]) }}</div>
                        <div class="finance-note">
                            <strong>{{ $persen($summary["total_premi"], max(1, $summary["total_payroll"])) }}%</strong>
                            dari payroll
                        </div>
                    </div>
                    <span class="finance-icon success"><i class="mdi mdi-chart-donut"></i></span>
                </div>
            </div>

            <div class="finance-metric">
                <div class="finance-metric-top">
                    <div>
                        <div class="finance-label">Pegawai Terbayar</div>
                        <div class="finance-value">{{ $angka($summary["pegawai_unique"]) }}</div>
                        <div class="finance-note">
                            Tahap 1 {{ $angka($summary["stage1"]["jumlah"]) }} dan tahap 2 {{ $angka($summary["stage2"]["jumlah"]) }}
                        </div>
                    </div>
                    <span class="finance-icon info"><i class="mdi mdi-account-group-outline"></i></span>
                </div>
            </div>

            <div class="finance-metric">
                <div class="finance-metric-top">
                    <div>
                        <div class="finance-label">Generator Terkunci</div>
                        <div class="finance-value">{{ $generator["summary"]["locked_rate"] }}%</div>
                        <div class="finance-note">
                            {{ $angka($generator["summary"]["locked_rows"]) }} dari {{ $angka($generator["summary"]["rows"]) }} baris
                        </div>
                    </div>
                    <span class="finance-icon warning"><i class="mdi mdi-lock-check-outline"></i></span>
                </div>
            </div>

            <div class="finance-metric">
                <div class="finance-metric-top">
                    <div>
                        <div class="finance-label">Nilai Generator</div>
                        <div class="finance-value">{{ $rupiah($generator["summary"]["amount"]) }}</div>
                        <div class="finance-note">
                            {{ $angka($generator["summary"]["actions"]) }} tindakan/baris aktivitas
                        </div>
                    </div>
                    <span class="finance-icon danger"><i class="mdi mdi-pulse"></i></span>
                </div>
            </div>
        </section>

        <section class="finance-insights">
            @foreach ($insights as $insight)
                <div class="finance-insight">
                    <span class="finance-icon {{ $insight["tone"] }}">
                        <i class="mdi {{ $insight["icon"] }}"></i>
                    </span>
                    <div>
                        <div class="finance-label">{{ $insight["label"] }}</div>
                        <div class="finance-insight-value">{{ $insight["value"] }}</div>
                        <div class="finance-insight-text">{{ $insight["text"] }}</div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="finance-grid">
            <div class="finance-panel">
                <div class="finance-panel-header">
                    <div>
                        <h2 class="finance-panel-title">Tren Payroll 6 Bulan</h2>
                        <p class="finance-panel-subtitle">Payroll, premi, dan potongan per periode.</p>
                    </div>
                </div>
                <div id="financeTrendChart" class="finance-chart"></div>
            </div>

            <div class="finance-panel">
                <div class="finance-panel-header">
                    <div>
                        <h2 class="finance-panel-title">Komposisi Payroll</h2>
                        <p class="finance-panel-subtitle">Komponen periode {{ $periodeLabel }}.</p>
                    </div>
                </div>
                <div id="financeCompositionChart" class="finance-chart-sm"></div>
            </div>
        </section>

        <section class="finance-grid-balanced">
            <div class="finance-panel">
                <div class="finance-panel-header">
                    <div>
                        <h2 class="finance-panel-title">Ringkasan Penggajian</h2>
                        <p class="finance-panel-subtitle">Komposisi tahap payroll, komponen, dan sebaran pegawai.</p>
                    </div>
                </div>

                @php
                    $stage1 = $summary["stage1"];
                    $stage2 = $summary["stage2"];
                    $stagePayrollRaw = (int) $stage1["total"] + (int) $stage2["total"];
                    $stagePayrollTotal = max(1, $stagePayrollRaw);
                    $stage1Share = $persen($stage1["total"], $stagePayrollTotal);
                    $stage2Share = $persen($stage2["total"], $stagePayrollTotal);
                    $stage1Average = (int) $stage1["jumlah"] > 0 ? round((int) $stage1["total"] / (int) $stage1["jumlah"]) : 0;
                    $stage2Average = (int) $stage2["jumlah"] > 0 ? round((int) $stage2["total"] / (int) $stage2["jumlah"]) : 0;
                @endphp

                <div class="finance-payroll-overview">
                    <div class="finance-payroll-head">
                        <div>
                            <div class="finance-label">Total Payroll Dua Tahap</div>
                            <div class="finance-payroll-head-value">{{ $rupiah($stagePayrollRaw) }}</div>
                            <div class="finance-payroll-head-meta">
                                {{ $angka($summary["pegawai_unique"]) }} pegawai unik, premi {{ $rupiah($summary["total_premi"]) }}
                            </div>
                        </div>
                        <div class="finance-payroll-split">
                            <div class="finance-payroll-split-track">
                                <div class="finance-payroll-split-fill stage-one" style="--split: {{ $stage1Share }}%;"></div>
                                <div class="finance-payroll-split-fill stage-two" style="--split: {{ $stage2Share }}%;"></div>
                            </div>
                            <div class="finance-payroll-split-legend">
                                <span>Tahap 1 {{ $stage1Share }}%</span>
                                <span>Tahap 2 {{ $stage2Share }}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="finance-stage-grid">
                        <div class="finance-stage stage-one">
                            <div class="finance-stage-title">
                                <div class="finance-stage-title-main">
                                    <i class="mdi mdi-numeric-1-circle-outline"></i>
                                    <div>
                                        <div class="finance-stage-name">Gaji Tahap 1</div>
                                        <div class="finance-stage-caption">Gapok, tunjangan, premi awal</div>
                                    </div>
                                </div>
                                <span class="finance-stage-share">{{ $stage1Share }}%</span>
                            </div>
                            <div class="finance-stage-total">{{ $rupiah($stage1["total"]) }}</div>
                            <div class="finance-stage-meta">
                                <span><strong>{{ $angka($stage1["jumlah"]) }}</strong>Pegawai</span>
                                <span><strong>{{ $rupiah($stage1Average) }}</strong>Rata-rata</span>
                                <span><strong>{{ $persen($stage1["premi"], max(1, (int) $stage1["total"])) }}%</strong>Rasio premi</span>
                            </div>

                            <div class="finance-stage-breakdown">
                                @foreach ([
                                    "Gaji dibayarkan" => (int) $stage1["gaji_dibayar"],
                                    "Tunjangan" => (int) $stage1["tunjangan"],
                                    "Premi" => (int) $stage1["premi"],
                                ] as $label => $value)
                                    <div class="finance-stage-line">
                                        <div class="finance-stage-line-head">
                                            <span>{{ $label }}</span>
                                            <strong>{{ $rupiah($value) }}</strong>
                                        </div>
                                        <div class="finance-stage-progress">
                                            <div class="finance-stage-progress-fill" style="--bar: {{ $persen($value, max(1, (int) $stage1["total"])) }}%;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="finance-stage-workforce">
                                <span><strong>{{ $angka($stage1["tetap"]) }}</strong>Tetap</span>
                                <span><strong>{{ $angka($stage1["kontrak"]) }}</strong>Kontrak</span>
                                <span><strong>{{ $angka($stage1["lainnya"]) }}</strong>Lainnya</span>
                            </div>
                        </div>

                        <div class="finance-stage stage-two">
                            <div class="finance-stage-title">
                                <div class="finance-stage-title-main">
                                    <i class="mdi mdi-numeric-2-circle-outline"></i>
                                    <div>
                                        <div class="finance-stage-name">Gaji Tahap 2</div>
                                        <div class="finance-stage-caption">Premi lanjutan dan potongan</div>
                                    </div>
                                </div>
                                <span class="finance-stage-share">{{ $stage2Share }}%</span>
                            </div>
                            <div class="finance-stage-total">{{ $rupiah($stage2["total"]) }}</div>
                            <div class="finance-stage-meta">
                                <span><strong>{{ $angka($stage2["jumlah"]) }}</strong>Pegawai</span>
                                <span><strong>{{ $rupiah($stage2Average) }}</strong>Rata-rata</span>
                                <span><strong>{{ $persen($stage2["potongan"], max(1, (int) $stage2["gaji_dibayar"] + (int) $stage2["premi"])) }}%</strong>Rasio potongan</span>
                            </div>

                            <div class="finance-stage-breakdown">
                                @foreach ([
                                    "Gaji dibayarkan" => (int) $stage2["gaji_dibayar"],
                                    "Premi" => (int) $stage2["premi"],
                                    "Potongan" => (int) $stage2["potongan"],
                                ] as $label => $value)
                                    <div class="finance-stage-line">
                                        <div class="finance-stage-line-head">
                                            <span>{{ $label }}</span>
                                            <strong>{{ $rupiah($value) }}</strong>
                                        </div>
                                        <div class="finance-stage-progress">
                                            <div class="finance-stage-progress-fill" style="--bar: {{ $persen($value, max(1, (int) $stage2["gaji_dibayar"] + (int) $stage2["premi"])) }}%;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="finance-stage-workforce">
                                <span><strong>{{ $angka($stage2["tetap"]) }}</strong>Tetap</span>
                                <span><strong>{{ $angka($stage2["kontrak"]) }}</strong>Kontrak</span>
                                <span><strong>{{ $angka($stage2["lainnya"]) }}</strong>Lainnya</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="finance-panel">
                <div class="finance-panel-header">
                    <div>
                        <h2 class="finance-panel-title">Top Sumber Premi</h2>
                        <p class="finance-panel-subtitle">Sumber nominal premi terbesar.</p>
                    </div>
                </div>

                @if ($premium["sources"]->isEmpty())
                    <div class="finance-empty">Belum ada rincian sumber premi.</div>
                @else
                    <div class="finance-source-list">
                        @foreach ($premium["sources"] as $source)
                            @php
                                $bar = round(((int) $source["total"] / $maxSource) * 100, 1);
                            @endphp
                            <div>
                                <div class="finance-source-row">
                                    <div>
                                        <div class="finance-source-name">{{ $source["label"] }}</div>
                                        <div class="finance-source-meta">
                                            {{ $angka($source["pegawai"]) }} pegawai, {{ $angka($source["jumlah"]) }} detail
                                        </div>
                                    </div>
                                    <div class="finance-source-total">{{ $rupiah($source["total"]) }}</div>
                                </div>
                                <div class="finance-bar">
                                    <div class="finance-bar-fill" style="--bar: {{ $bar }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="finance-grid-balanced">
            <div class="finance-panel">
                <div class="finance-panel-header">
                    <div>
                        <h2 class="finance-panel-title">Penerima Premi Terbesar</h2>
                        <p class="finance-panel-subtitle">Akumulasi premi tahap 1 dan tahap 2.</p>
                    </div>
                </div>

                @if ($premium["recipients"]->isEmpty())
                    <div class="finance-empty">Belum ada penerima premi pada periode ini.</div>
                @else
                    <div class="finance-recipient-list">
                        @foreach ($premium["recipients"] as $recipient)
                            <div class="finance-recipient-row">
                                <div>
                                    <div class="finance-recipient-name">{{ $recipient["nama"] }}</div>
                                    <div class="finance-recipient-meta">
                                        {{ $recipient["nik"] }} - {{ $recipient["jabatan"] }} - {{ $recipient["tahap"] }}
                                    </div>
                                </div>
                                <div class="finance-recipient-total">{{ $rupiah($recipient["total_premi"]) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="finance-panel">
                <div class="finance-panel-header">
                    <div>
                        <h2 class="finance-panel-title">Generator Premi Teratas</h2>
                        <p class="finance-panel-subtitle">Nilai dan volume dari modul generator.</p>
                    </div>
                </div>

                @if ($generator["top"]->isEmpty())
                    <div class="finance-empty">Belum ada aktivitas generator.</div>
                @else
                    <div class="finance-source-list">
                        @foreach ($generator["top"] as $item)
                            @php
                                $base = (int) $item["amount"] > 0 ? $maxGenerator : max(1, (int) $generator["top"]->max("count"));
                                $value = (int) $item["amount"] > 0 ? (int) $item["amount"] : (int) $item["count"];
                                $bar = round(($value / $base) * 100, 1);
                            @endphp
                            <div>
                                <div class="finance-source-row">
                                    <div>
                                        <div class="finance-source-name">{{ $item["label"] }}</div>
                                        <div class="finance-source-meta">
                                            {{ $angka($item["count"]) }} baris, {{ $angka($item["actions"]) }} tindakan
                                        </div>
                                    </div>
                                    <div class="finance-source-total">
                                        {{ (int) $item["amount"] > 0 ? $rupiah($item["amount"]) : $angka($item["count"]) . " baris" }}
                                    </div>
                                </div>
                                <div class="finance-bar">
                                    <div class="finance-bar-fill" style="--bar: {{ $bar }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="finance-panel">
            <div class="finance-panel-header">
                <div>
                    <h2 class="finance-panel-title">Status Generator Modul Keuangan</h2>
                    <p class="finance-panel-subtitle">
                        {{ $generator["summary"]["generated_modules"] }} dari {{ $generator["summary"]["total_modules"] }} modul punya data periode {{ $periodeLabel }}.
                    </p>
                </div>
                <span class="finance-badge {{ $generator["summary"]["locked_rate"] >= 90 ? "success" : "warning" }}">
                    Lock {{ $generator["summary"]["locked_rate"] }}%
                </span>
            </div>

            <div class="finance-generator-grid">
                @foreach ($generator["items"] as $item)
                    <div class="finance-generator-card">
                        <div class="finance-generator-head">
                            <div>
                                <div class="finance-generator-name">{{ $item["label"] }}</div>
                                <div class="finance-generator-meta">{{ $item["table"] }}</div>
                            </div>
                            @if (! $item["available"])
                                <span class="finance-badge muted">Belum tersedia</span>
                            @elseif ($item["count"] > 0)
                                <span class="finance-badge success">Generated</span>
                            @else
                                <span class="finance-badge warning">Kosong</span>
                            @endif
                        </div>

                        <div class="finance-bar">
                            <div class="finance-bar-fill" style="--bar: {{ min(100, (float) $item["locked_rate"]) }}%;"></div>
                        </div>
                        <div class="finance-generator-number">
                            <span>Baris</span>
                            <strong>{{ $angka($item["count"]) }}</strong>
                        </div>
                        <div class="finance-generator-number">
                            <span>Terkunci</span>
                            <strong>{{ $angka($item["locked_count"]) }}</strong>
                        </div>
                        <div class="finance-generator-number">
                            <span>Nilai</span>
                            <strong>{{ $rupiah($item["amount"]) }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection

@push("scripts")
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rupiah = function(value) {
                const number = Number(value || 0);
                return 'Rp ' + number.toLocaleString('id-ID');
            };

            const hasPositiveValue = function(values) {
                return values.some(function(value) {
                    return Number(value || 0) > 0;
                });
            };

            const emptyChart = function(selector, message) {
                const element = document.querySelector(selector);
                if (element) {
                    element.innerHTML = '<div class="finance-empty">' + message + '</div>';
                }
            };

            if (window.ApexCharts) {
                const trendLabels = @json($trend["labels"]);
                const trendPayroll = @json($trend["payroll"]);
                const trendPremi = @json($trend["premi"]);
                const trendPotongan = @json($trend["potongan"]);

                if (hasPositiveValue(trendPayroll) || hasPositiveValue(trendPremi) || hasPositiveValue(trendPotongan)) {
                    new ApexCharts(document.querySelector('#financeTrendChart'), {
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'inherit'
                        },
                        series: [{
                                name: 'Payroll',
                                data: trendPayroll
                            },
                            {
                                name: 'Premi',
                                data: trendPremi
                            },
                            {
                                name: 'Potongan',
                                data: trendPotongan
                            }
                        ],
                        colors: ['#2563eb', '#16a34a', '#dc2626'],
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: .18,
                                opacityFrom: .28,
                                opacityTo: .04,
                                stops: [0, 90, 100]
                            }
                        },
                        grid: {
                            borderColor: '#e5e7eb',
                            strokeDashArray: 4
                        },
                        xaxis: {
                            categories: trendLabels,
                            labels: {
                                style: {
                                    colors: '#64748b'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: function(value) {
                                    if (value >= 1000000000) {
                                        return 'Rp ' + (value / 1000000000).toFixed(1) + 'M';
                                    }
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                    }
                                    return rupiah(value);
                                },
                                style: {
                                    colors: '#64748b'
                                }
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: rupiah
                            }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left'
                        }
                    }).render();
                } else {
                    emptyChart('#financeTrendChart', 'Belum ada data tren.');
                }

                const components = @json($summary["component"]);
                const visibleComponents = components.filter(function(item) {
                    return Number(item.value || 0) > 0;
                });

                if (visibleComponents.length > 0) {
                    new ApexCharts(document.querySelector('#financeCompositionChart'), {
                        chart: {
                            type: 'donut',
                            height: 270,
                            fontFamily: 'inherit'
                        },
                        series: visibleComponents.map(function(item) {
                            return Number(item.value || 0);
                        }),
                        labels: visibleComponents.map(function(item) {
                            return item.label;
                        }),
                        colors: ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#0e7490'],
                        stroke: {
                            width: 0
                        },
                        dataLabels: {
                            enabled: false
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            formatter: function(w) {
                                                const total = w.globals.seriesTotals.reduce(function(sum, value) {
                                                    return sum + value;
                                                }, 0);
                                                return rupiah(total);
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: rupiah
                            }
                        }
                    }).render();
                } else {
                    emptyChart('#financeCompositionChart', 'Belum ada komposisi payroll.');
                }
            } else {
                emptyChart('#financeTrendChart', 'Chart belum dapat dimuat.');
                emptyChart('#financeCompositionChart', 'Chart belum dapat dimuat.');
            }
        });
    </script>
@endpush
