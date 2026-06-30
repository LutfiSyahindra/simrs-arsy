@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        body {
            background: #f5f7fb;
        }

        .non-medis-page {
            --nm-blue: #2563eb;
            --nm-navy: #102a43;
            --nm-muted: #64748b;
            --nm-line: #e2e8f0;
            color: #172033;
        }

        .non-medis-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .non-medis-hero {
            align-items: center;
            background:
                radial-gradient(circle at 88% 8%, rgba(96, 165, 250, .36), transparent 28%),
                linear-gradient(135deg, #102a43 0%, #1e3a8a 55%, #2563eb 100%);
            border-radius: 18px;
            box-shadow: 0 18px 38px rgba(30, 58, 138, .2);
            color: #fff;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            margin-top: 15px;
            overflow: hidden;
            padding: 24px;
            position: relative;
        }

        .non-medis-hero::after {
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 50%;
            content: "";
            height: 220px;
            position: absolute;
            right: -55px;
            top: -110px;
            width: 220px;
        }

        .non-medis-hero-main {
            align-items: center;
            display: flex;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .non-medis-hero-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 14px;
            display: flex;
            flex: 0 0 auto;
            font-size: 29px;
            height: 60px;
            justify-content: center;
            width: 60px;
        }

        .non-medis-eyebrow {
            color: #bfdbfe;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .non-medis-title {
            font-size: 23px;
            font-weight: 800;
            margin: 3px 0 5px;
        }

        .non-medis-description {
            color: rgba(255, 255, 255, .75);
            font-size: 13px;
            margin: 0;
            max-width: 700px;
        }

        .non-medis-period {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 12px;
            flex: 0 0 230px;
            padding: 12px;
            position: relative;
            z-index: 1;
        }

        .non-medis-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .non-medis-hero-controls .non-medis-period {
            flex: none;
        }

        .non-medis-period label {
            color: #dbeafe;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .non-medis-period .form-control {
            background: #fff;
            border: 0;
            height: 39px;
        }

        .non-medis-period .form-select {
            background-color: #fff;
            border: 0;
            height: 39px;
        }

        .non-medis-type-tabs {
            background: #fff;
            border: 1px solid var(--nm-line);
            border-radius: 14px;
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 8px;
        }

        .non-medis-type-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 10px;
            color: #64748b;
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 11px 14px;
            text-align: left;
            transition: all .18s ease;
        }

        .non-medis-type-tab i {
            font-size: 23px;
        }

        .non-medis-type-tab strong,
        .non-medis-type-tab small {
            display: block;
        }

        .non-medis-type-tab small {
            font-size: 11px;
            margin-top: 1px;
        }

        .non-medis-type-tab.active {
            background: #eff6ff;
            box-shadow: inset 0 0 0 1px #bfdbfe;
            color: #1d4ed8;
        }

        .non-medis-panel {
            background: #fff;
            border: 1px solid var(--nm-line);
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .non-medis-panel-head {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .non-medis-panel-title {
            color: var(--nm-navy);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .non-medis-panel-subtitle {
            color: var(--nm-muted);
            font-size: 11px;
        }

        .non-medis-dependencies {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 15px 17px 0;
        }

        .non-medis-dependency {
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--nm-line);
            border-radius: 11px;
            display: flex;
            gap: 11px;
            padding: 12px;
        }

        .non-medis-dependency-icon {
            align-items: center;
            background: #e2e8f0;
            border-radius: 9px;
            color: #64748b;
            display: flex;
            flex: 0 0 auto;
            font-size: 21px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .non-medis-dependency.ready {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .non-medis-dependency.ready .non-medis-dependency-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .non-medis-dependency.unlocked {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .non-medis-dependency.unlocked .non-medis-dependency-icon {
            background: #fef3c7;
            color: #b45309;
        }

        .non-medis-dependency.missing {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .non-medis-dependency.missing .non-medis-dependency-icon {
            background: #ffedd5;
            color: #c2410c;
        }

        .non-medis-dependency-label {
            color: #475569;
            font-size: 11px;
        }

        .non-medis-dependency-value {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .non-medis-dependency-note {
            color: #64748b;
            font-size: 10px;
            margin-top: 1px;
        }

        .non-medis-source-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 12px 17px 0;
        }

        .non-medis-source-field {
            background: #f8fafc;
            border: 1px solid var(--nm-line);
            border-radius: 11px;
            padding: 12px;
        }

        .non-medis-source-field label {
            color: #475569;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .non-medis-source-field .form-select {
            font-size: 12px;
            min-height: 37px;
        }

        .non-medis-source-note {
            color: #64748b;
            display: block;
            font-size: 10px;
            margin-top: 6px;
        }

        .non-medis-summary-grid {
            display: grid;
            gap: 11px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 15px 17px;
        }

        .non-medis-summary-card {
            background: linear-gradient(180deg, #fff, #f8fafc);
            border: 1px solid var(--nm-line);
            border-radius: 11px;
            min-width: 0;
            padding: 13px;
        }

        .non-medis-summary-card.primary {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-color: #bfdbfe;
        }

        .non-medis-summary-card.total {
            background: linear-gradient(135deg, #102a43, #1d4ed8);
            border: 0;
            color: #fff;
        }

        .non-medis-summary-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .non-medis-summary-card.total .non-medis-summary-label {
            color: #bfdbfe;
        }

        .non-medis-summary-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            margin-top: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .non-medis-summary-card.total .non-medis-summary-value {
            color: #fff;
        }

        .non-medis-summary-before {
            color: rgba(255, 255, 255, .88);
            font-size: 11px;
            font-weight: 800;
            margin-top: 7px;
        }

        .non-medis-summary-note {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 3px;
        }

        .non-medis-summary-card.total .non-medis-summary-note {
            color: #bfdbfe;
        }

        .non-medis-formula {
            align-items: center;
            background: #f8fafc;
            border-top: 1px solid #eef2f7;
            color: #475569;
            display: flex;
            font-size: 12px;
            gap: 9px;
            padding: 12px 17px;
        }

        .non-medis-formula i {
            color: #2563eb;
            font-size: 19px;
        }

        .non-medis-preview {
            border-top: 1px solid #eef2f7;
            padding: 14px 17px 17px;
        }

        .non-medis-preview-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .non-medis-preview-title {
            color: var(--nm-navy);
            font-size: 13px;
            font-weight: 800;
        }

        .non-medis-preview-note {
            color: var(--nm-muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .non-medis-preview-count {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .non-medis-preview-table {
            margin: 0;
        }

        .non-medis-preview-table th {
            background: #f8fafc;
            border-bottom: 1px solid var(--nm-line) !important;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .non-medis-preview-table td {
            border-color: #eef2f7;
            color: #334155;
            font-size: 11px;
            vertical-align: middle;
        }

        .non-medis-preview-result {
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 900;
        }

        .non-medis-preview-formula {
            color: #64748b;
            display: block;
            font-size: 10px;
            margin-top: 2px;
        }

        .non-medis-generate {
            align-items: center;
            background: #2563eb;
            border: 0;
            border-radius: 9px;
            color: #fff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 15px;
        }

        .non-medis-panel-actions {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .non-medis-config-btn {
            align-items: center;
            background: #fff;
            border: 1px solid var(--nm-line);
            border-radius: 9px;
            color: #334155;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            padding: 10px 13px;
        }

        .non-medis-config-btn:hover,
        .non-medis-config-btn:focus {
            background: #f8fafc;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .non-medis-generate:hover,
        .non-medis-generate:focus {
            background: #1d4ed8;
            color: #fff;
        }

        .non-medis-generate:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .non-medis-table-wrap {
            padding: 8px 17px 17px;
        }

        .non-medis-table thead th {
            border-bottom: 1px solid var(--nm-line) !important;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            padding: 11px 9px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .non-medis-table tbody td {
            border-color: #eef2f7;
            color: #334155;
            font-size: 12px;
            padding: 11px 9px;
            vertical-align: middle;
        }

        .non-medis-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 8px;
        }

        .non-medis-badge.umum {
            background: #dcfce7;
            color: #166534;
        }

        .non-medis-badge.bpjs {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .non-medis-lock {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            padding: 4px 8px;
        }

        .non-medis-lock.open {
            background: #f1f5f9;
            color: #64748b;
        }

        .non-medis-lock.locked {
            background: #fef3c7;
            color: #92400e;
        }

        .non-medis-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .non-medis-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 31px;
            justify-content: center;
            padding: 0;
            width: 31px;
        }

        .non-medis-detail-header {
            align-items: center;
            background: linear-gradient(135deg, #102a43, #2563eb);
            color: #fff;
            display: flex;
            justify-content: space-between;
            padding: 17px 20px;
        }

        .non-medis-detail-header-main {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .non-medis-detail-header-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border-radius: 10px;
            display: flex;
            font-size: 23px;
            height: 45px;
            justify-content: center;
            width: 45px;
        }

        .non-medis-detail-header .btn-close {
            filter: invert(1);
        }

        .non-medis-detail-metrics {
            display: grid;
            gap: 9px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-bottom: 14px;
        }

        .non-medis-detail-metric {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 10px;
        }

        .non-medis-detail-metric span {
            color: #64748b;
            display: block;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .non-medis-detail-metric strong {
            color: #0f172a;
            display: block;
            font-size: 13px;
            margin-top: 3px;
        }

        .non-medis-detail-metric.total {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .non-medis-detail-section {
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .non-medis-detail-section-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            padding: 11px 13px;
        }

        .non-medis-detail-section-title {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .non-medis-detail-table {
            margin: 0;
        }

        .non-medis-detail-table th {
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .non-medis-detail-table td {
            color: #334155;
            font-size: 11px;
            vertical-align: middle;
        }

        .non-medis-mapping-row {
            cursor: pointer;
        }

        .non-medis-mapping-row.active {
            background: #eff6ff;
        }

        .non-medis-kind {
            border-radius: 999px;
            display: inline-flex;
            font-size: 9px;
            font-weight: 800;
            padding: 3px 7px;
            text-transform: uppercase;
        }

        .non-medis-kind.persen {
            background: #dcfce7;
            color: #166534;
        }

        .non-medis-kind.nominal {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .non-medis-provider-name {
            color: #0f172a;
            font-weight: 800;
        }

        .non-medis-provider-total {
            background: #f8fafc;
            font-weight: 800;
        }

        .non-medis-transaction-toolbar {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .non-medis-transaction-toolbar .form-select,
        .non-medis-transaction-toolbar .form-control {
            font-size: 11px;
            height: 34px;
        }

        .non-medis-empty {
            color: #94a3b8;
            padding: 30px;
            text-align: center;
        }

        .karcis-config-list {
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            max-height: 430px;
            overflow: auto;
        }

        .karcis-config-item {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 11px;
            padding: 11px 13px;
        }

        .karcis-config-item:last-child {
            border-bottom: 0;
        }

        .karcis-config-main {
            min-width: 0;
        }

        .karcis-config-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .karcis-config-meta {
            color: #64748b;
            font-size: 10px;
            margin-top: 2px;
        }

        @media (max-width: 1199.98px) {
            .non-medis-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .non-medis-detail-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .non-medis-hero,
            .non-medis-panel-head,
            .non-medis-preview-head,
            .non-medis-panel-actions,
            .non-medis-transaction-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .non-medis-hero-controls,
            .non-medis-period {
                flex-basis: auto;
                width: 100%;
            }

            .non-medis-type-tabs,
            .non-medis-dependencies,
            .non-medis-source-grid {
                grid-template-columns: 1fr;
            }

            .non-medis-type-tabs {
                display: grid;
            }

            .non-medis-summary-grid,
            .non-medis-detail-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section("content")
    <div class="non-medis-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Pelayanan Non Medis</li>
            </ol>
        </nav>

        <section class="non-medis-hero">
            <div class="non-medis-hero-main">
                <div class="non-medis-hero-icon">
                    <i class="mdi mdi-calculator-variant-outline"></i>
                </div>
                <div>
                    <div class="non-medis-eyebrow">Premium Generator</div>
                    <h4 class="non-medis-title">Generate Premi Pelayanan Non Medis</h4>
                    <p class="non-medis-description">
                        Hitung premi dari enam sumber tindakan Khanza, mapping tindakan, mapping premi,
                        BHP, dan lama inap dengan snapshot perhitungan yang dapat diaudit.
                    </p>
                </div>
            </div>
            <div class="non-medis-hero-controls">
                <div class="non-medis-period">
                    <label for="periodeNonMedis">Periode Perhitungan</label>
                    <input type="month" id="periodeNonMedis" class="form-control">
                    <small class="d-block mt-1 text-white-50" id="nonMedisSourcePeriod">
                        Sumber data mengikuti periode yang dipilih.
                    </small>
                </div>
                <div class="non-medis-period">
                    <label>Konfigurasi Aktif</label>
                    <div class="fw-bold" id="activeConfigPremiLabel">Memuat konfigurasi...</div>
                    <small class="d-block mt-1 text-white-50" id="mappingPremiNonMedisNote">
                        Mapping premi dan karcis diatur dari menu konfigurasi.
                    </small>
                </div>
            </div>
        </section>

        <div class="non-medis-type-tabs">
            <button type="button" class="non-medis-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>Non-BPJ, piutang aktif dan aturan khusus A09</small>
                </span>
            </button>
            <button type="button" class="non-medis-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>Kode BPJ, piutang Belum Lunas, sumber bulan sebelumnya</small>
                </span>
            </button>
        </div>

        <section class="non-medis-panel">
            <div class="non-medis-panel-head">
                <div>
                    <div class="non-medis-panel-title">Kesiapan dan Preview Perhitungan</div>
                    <div class="non-medis-panel-subtitle" id="nonMedisSummarySubtitle">
                        Memeriksa data periode terpilih...
                    </div>
                </div>
                <div class="non-medis-panel-actions">
                    <button type="button" id="btnKarcisConfig" class="non-medis-config-btn">
                        <i class="mdi mdi-tune-variant"></i>
                        <span>Konfigurasi</span>
                    </button>
                    <button type="button" id="btnGenerateNonMedis" class="non-medis-generate" disabled>
                        <i class="mdi mdi-cog-play-outline"></i>
                        <span>Generate Premi</span>
                    </button>
                </div>
            </div>

            <div class="non-medis-dependencies">
                <div class="non-medis-dependency" id="dependencyBhp">
                    <div class="non-medis-dependency-icon">
                        <i class="mdi mdi-medical-bag"></i>
                    </div>
                    <div>
                        <div class="non-medis-dependency-label">Generate BHP</div>
                        <div class="non-medis-dependency-value" id="dependencyBhpValue">Memeriksa...</div>
                        <div class="non-medis-dependency-note" id="dependencyBhpNote">-</div>
                    </div>
                </div>
                <div class="non-medis-dependency" id="dependencyKamar">
                    <div class="non-medis-dependency-icon">
                        <i class="mdi mdi-bed-outline"></i>
                    </div>
                    <div>
                        <div class="non-medis-dependency-label">Generate Kamar Inap</div>
                        <div class="non-medis-dependency-value" id="dependencyKamarValue">Memeriksa...</div>
                        <div class="non-medis-dependency-note" id="dependencyKamarNote">-</div>
                    </div>
                </div>
            </div>

            <div class="non-medis-source-grid">
                <div class="non-medis-source-field">
                    <label for="sourceBhpNonMedis">Sumber Data BHP</label>
                    <select id="sourceBhpNonMedis" class="form-select">
                        <option value="">Memuat sumber BHP...</option>
                    </select>
                    <small class="non-medis-source-note" id="sourceBhpNonMedisNote">
                        Pilih hasil BHP sesuai ploting premi.
                    </small>
                </div>
                <div class="non-medis-source-field">
                    <label for="sourceKamarNonMedis">Sumber Data Kamar</label>
                    <select id="sourceKamarNonMedis" class="form-select">
                        <option value="">Memuat sumber Kamar...</option>
                    </select>
                    <small class="non-medis-source-note" id="sourceKamarNonMedisNote">
                        Pilih hasil Kamar sesuai ploting premi.
                    </small>
                </div>
            </div>

            <div class="non-medis-summary-grid">
                <div class="non-medis-summary-card">
                    <div class="non-medis-summary-label">Transaksi Sesuai Mapping</div>
                    <div class="non-medis-summary-value" id="summaryTransaksi">0</div>
                    <div class="non-medis-summary-note" id="summaryTindakan">0 jenis tindakan</div>
                </div>
                <div class="non-medis-summary-card">
                    <div class="non-medis-summary-label">Total Biaya Rawat</div>
                    <div class="non-medis-summary-value" id="summaryBiayaRawat">Rp 0</div>
                    <div class="non-medis-summary-note">Dasar hitung mapping persen</div>
                </div>
                <div class="non-medis-summary-card primary">
                    <div class="non-medis-summary-label">Hasil Mapping Premi</div>
                    <div class="non-medis-summary-value" id="summaryMapping">Rp 0</div>
                    <div class="non-medis-summary-note" id="summaryJumlahMapping">0 mapping terhitung</div>
                </div>
                <div class="non-medis-summary-card total">
                    <div class="non-medis-summary-label">Total Nilai Final</div>
                    <div class="non-medis-summary-value" id="summaryFinal">Rp 0</div>
                    <div class="non-medis-summary-before" id="summarySebelumPembagi">
                        Sebelum pembagi: Rp 0
                    </div>
                    <div class="non-medis-summary-note" id="summaryPembagiNote">Pembagi: 1</div>
                </div>
            </div>

            <div class="non-medis-formula">
                <i class="mdi mdi-function-variant"></i>
                <span id="summaryFormula">(Rp 0 + Rp 0 + Rp 0) / 1 = Rp 0</span>
            </div>

            <div class="non-medis-preview">
                <div class="non-medis-preview-head">
                    <div>
                        <div class="non-medis-preview-title">Preview Hasil Tindakan</div>
                        <div class="non-medis-preview-note">
                            Perbandingan dasar tindakan sebelum dan setelah nilai hitung mapping premi diterapkan.
                        </div>
                    </div>
                    <span class="non-medis-preview-count" id="summaryPreviewCount">0 tindakan</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover non-medis-preview-table">
                        <thead>
                            <tr>
                                <th>Tindakan</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Total Biaya Rawat</th>
                                <th class="text-end">Sebelum Hitung</th>
                                <th class="text-end">Nilai Hitung</th>
                                <th class="text-end">Setelah Hitung</th>
                            </tr>
                        </thead>
                        <tbody id="summaryPreviewRows">
                            <tr>
                                <td colspan="6" class="non-medis-empty">
                                    Pilih mapping premi untuk melihat preview tindakan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="non-medis-panel">
            <div class="non-medis-panel-head">
                <div>
                    <div class="non-medis-panel-title">Riwayat Generate</div>
                    <div class="non-medis-panel-subtitle">
                        Hasil tersimpan per periode, jenis pelayanan, dan mapping premi.
                    </div>
                </div>
                <span class="non-medis-badge umum" id="activeTypeBadge">UMUM</span>
            </div>
            <div class="non-medis-table-wrap">
                <div class="table-responsive">
                    <table id="tableGenerateNonMedis" class="table table-hover non-medis-table w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Premi</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Hasil Mapping</th>
                                <th class="text-end">BHP</th>
                                <th class="text-end">Kamar</th>
                                <th class="text-end">Total Final</th>
                                <th>Status</th>
                                <th>Generate Oleh</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="modal fade" id="modalNonMedisConfig" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-sm">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold">Konfigurasi Pelayanan Non Medis</h5>
                            <small class="text-muted">
                                Pilih sumber mapping premi dan tindakan karcis BPJS yang berlaku untuk generate.
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="configMappingPremiNonMedis" class="form-label fw-semibold">
                                Sumber Mapping Premi
                            </label>
                            <select id="configMappingPremiNonMedis" class="form-select">
                                <option value="">Memuat mapping premi...</option>
                            </select>
                            <small class="text-muted" id="configMappingPremiNote">
                                Pegawai penerima diambil dari mapping premi yang dipilih.
                            </small>
                            <div class="mt-3">
                                <label for="configDistributionModeNonMedis" class="form-label fw-semibold">
                                    Distribusi Nilai Final
                                </label>
                                <select id="configDistributionModeNonMedis" class="form-select">
                                    <option value="split_evenly">Dibagi rata ke pegawai</option>
                                    <option value="full_amount">Nilai final penuh untuk setiap pegawai</option>
                                </select>
                                <small class="text-muted" id="configDistributionModeNote">
                                    Dibagi rata: total final dibagi jumlah pegawai penerima.
                                </small>
                            </div>
                            <div class="mt-3 rounded bg-light p-3">
                                <div class="fw-semibold mb-1">Pegawai Penerima</div>
                                <div class="small text-muted" id="configPegawaiPreview">
                                    Pilih mapping premi untuk melihat pegawai penerima.
                                </div>
                            </div>
                        </div>

                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text bg-white">
                                <i class="mdi mdi-magnify text-muted"></i>
                            </span>
                            <input type="search" id="karcisConfigSearch" class="form-control"
                                placeholder="Cari kode atau nama jenis tindakan...">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Centang jenis tindakan yang termasuk karcis BPJS.</small>
                            <span class="badge bg-light text-dark" id="karcisConfigSelectedCount">0 dipilih</span>
                        </div>
                        <div class="karcis-config-list" id="karcisConfigList">
                            <div class="non-medis-empty">
                                Memuat tindakan...
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="btnSaveKarcisConfig">
                            <i class="mdi mdi-content-save-outline me-1"></i>
                            Simpan Konfigurasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @include("simrs.backOffice.keuangan.hitungPremi.generatePelayananNonMedis.modal")
    </div>
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generatePelayananNonMedis.jsMain")
@endpush
