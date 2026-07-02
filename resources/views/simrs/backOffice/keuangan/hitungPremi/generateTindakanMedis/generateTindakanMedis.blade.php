@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        body {
            background: #f6f8fb;
        }

        .tm-page {
            --tm-ink: #172033;
            --tm-muted: #64748b;
            --tm-line: #dfe7f0;
            --tm-navy: #123047;
            --tm-teal: #0f766e;
            --tm-blue: #2563eb;
            --tm-amber: #d97706;
            color: var(--tm-ink);
        }

        .tm-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .tm-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #102a43 0%, #0f766e 58%, #2563eb 100%);
            border-radius: 8px;
            color: #fff;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1fr) 430px;
            margin-top: 15px;
            overflow: hidden;
            padding: 22px;
        }

        .tm-hero-main {
            align-items: center;
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .tm-hero-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            display: flex;
            flex: 0 0 auto;
            font-size: 29px;
            height: 58px;
            justify-content: center;
            width: 58px;
        }

        .tm-eyebrow {
            color: #ccfbf1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .tm-title {
            font-size: 23px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .tm-description {
            color: rgba(255, 255, 255, .78);
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            max-width: 760px;
        }

        .tm-hero-controls {
            display: grid;
            gap: 10px;
            grid-template-columns: 160px minmax(0, 1fr);
        }

        .tm-hero-field {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            padding: 12px;
        }

        .tm-hero-field label {
            color: #d9f99d;
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .tm-hero-field .form-control {
            border: 0;
            height: 38px;
        }

        .tm-active-premi {
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            margin-bottom: 4px;
        }

        .tm-active-note {
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
        }

        .tm-tabs {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin: 16px 0;
        }

        .tm-tab {
            align-items: center;
            background: #fff;
            border: 1px solid var(--tm-line);
            border-radius: 8px;
            color: #334155;
            display: flex;
            gap: 11px;
            padding: 13px 15px;
            text-align: left;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .tm-tab i {
            align-items: center;
            background: #eef2ff;
            border-radius: 8px;
            color: var(--tm-blue);
            display: flex;
            font-size: 22px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .tm-tab strong {
            display: block;
            font-size: 14px;
            line-height: 1.2;
        }

        .tm-tab small {
            color: var(--tm-muted);
            display: block;
            font-size: 11px;
            line-height: 1.35;
        }

        .tm-tab.active {
            border-color: var(--tm-teal);
            box-shadow: 0 8px 22px rgba(15, 118, 110, .12);
        }

        .tm-tab.active i {
            background: #ccfbf1;
            color: var(--tm-teal);
        }

        .tm-panel {
            background: #fff;
            border: 1px solid var(--tm-line);
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .tm-panel-head {
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            padding: 16px;
        }

        .tm-panel-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .tm-panel-subtitle {
            color: var(--tm-muted);
            font-size: 12px;
        }

        .tm-panel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .tm-btn {
            align-items: center;
            border: 0;
            border-radius: 8px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            min-height: 38px;
            padding: 9px 12px;
        }

        .tm-btn.config {
            background: #f1f5f9;
            color: #0f172a;
        }

        .tm-btn.generate {
            background: var(--tm-teal);
            color: #fff;
        }

        .tm-btn.generate:disabled {
            background: #cbd5e1;
            color: #f8fafc;
            cursor: not-allowed;
        }

        .tm-body {
            padding: 16px;
        }

        .tm-dependencies {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .tm-dependency {
            border: 1px solid var(--tm-line);
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: 42px minmax(0, 1fr);
            padding: 13px;
        }

        .tm-dependency-icon {
            align-items: center;
            background: #ecfeff;
            border-radius: 8px;
            color: #0891b2;
            display: flex;
            font-size: 22px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .tm-dependency-label {
            color: var(--tm-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .tm-dependency-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            margin: 2px 0;
        }

        .tm-dependency-note {
            color: var(--tm-muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .tm-source-grid,
        .tm-summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .tm-source-field {
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 8px;
            padding: 12px;
        }

        .tm-source-field label {
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .tm-source-note {
            color: var(--tm-muted);
            display: block;
            font-size: 11px;
            margin-top: 6px;
        }

        .tm-readiness-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .tm-readiness-item {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr);
            padding: 11px;
        }

        .tm-readiness-item.success {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .tm-readiness-item.warning {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .tm-readiness-item.danger {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .tm-readiness-icon {
            align-items: center;
            background: #e2e8f0;
            border-radius: 8px;
            color: #334155;
            display: flex;
            font-size: 18px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .tm-readiness-item.success .tm-readiness-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .tm-readiness-item.warning .tm-readiness-icon {
            background: #fef3c7;
            color: #b45309;
        }

        .tm-readiness-item.danger .tm-readiness-icon {
            background: #fee2e2;
            color: #b91c1c;
        }

        .tm-readiness-label {
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .tm-readiness-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            margin: 2px 0;
        }

        .tm-readiness-note {
            color: var(--tm-muted);
            font-size: 11px;
            line-height: 1.35;
        }

        .tm-info-strip,
        .tm-insight-strip {
            align-items: stretch;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .tm-info-pill {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: inline-flex;
            gap: 8px;
            min-height: 36px;
            padding: 8px 10px;
        }

        .tm-info-pill i {
            color: #0f766e;
            font-size: 16px;
        }

        .tm-info-pill span {
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .tm-info-pill strong {
            color: #0f172a;
            font-size: 12px;
            white-space: nowrap;
        }

        .tm-summary-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .tm-summary-card {
            border: 1px solid var(--tm-line);
            border-radius: 8px;
            padding: 13px;
        }

        .tm-summary-card.primary {
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .tm-summary-card.total {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .tm-summary-card.total .tm-summary-label,
        .tm-detail-metric.grand span {
            color: #047857;
        }

        .tm-summary-label {
            color: var(--tm-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .tm-summary-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            margin: 5px 0 2px;
        }

        .tm-summary-note,
        .tm-summary-before {
            color: var(--tm-muted);
            font-size: 11px;
            line-height: 1.35;
        }

        .tm-summary-before.strong {
            color: #0f766e;
            font-weight: 900;
        }

        .tm-formula {
            align-items: center;
            background: #102a43;
            border-radius: 8px;
            color: #dbeafe;
            display: flex;
            font-size: 12px;
            font-weight: 700;
            gap: 8px;
            margin-bottom: 12px;
            padding: 12px;
        }

        .tm-formula strong {
            color: #ffffff;
            font-weight: 900;
        }

        .tm-formula-muted {
            color: #bfdbfe;
        }

        .tm-preview-head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 9px;
        }

        .tm-preview-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .tm-preview-note {
            color: var(--tm-muted);
            font-size: 12px;
        }

        .tm-preview-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .tm-preview-actions .form-control,
        .tm-preview-actions .form-select {
            font-size: 12px;
            min-height: 34px;
            width: 190px;
        }

        .tm-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .tm-badge.umum {
            background: #ecfdf5;
            color: #047857;
        }

        .tm-badge.bpjs {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tm-table th,
        .tm-preview-table th,
        .tm-detail-table th {
            color: #475569;
            font-size: 11px;
            letter-spacing: .03em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .tm-table td,
        .tm-preview-table td,
        .tm-detail-table td {
            font-size: 12px;
            vertical-align: middle;
        }

        .tm-empty {
            color: var(--tm-muted);
            padding: 24px !important;
            text-align: center;
        }

        .tm-kind {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 8px;
            text-transform: uppercase;
        }

        .tm-kind.persen {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .tm-kind.nominal {
            background: #fef3c7;
            color: #92400e;
        }

        .tm-progress {
            background: #e2e8f0;
            border-radius: 999px;
            height: 5px;
            margin-top: 7px;
            overflow: hidden;
            width: 100%;
        }

        .tm-progress span {
            background: #2563eb;
            display: block;
            height: 100%;
        }

        .tm-actions {
            display: inline-flex;
            gap: 6px;
        }

        .tm-actions .btn {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            padding: 0;
            width: 32px;
        }

        .tm-config-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .tm-config-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .tm-source-rule {
            align-items: end;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, .9fr) minmax(0, 1.2fr) 38px;
            margin-bottom: 8px;
            padding: 10px;
        }

        .tm-source-rule label {
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .tm-karcis-list {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            max-height: 340px;
            overflow: auto;
        }

        .tm-karcis-item {
            align-items: center;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            gap: 11px;
            padding: 11px 13px;
        }

        .tm-karcis-item:last-child {
            border-bottom: 0;
        }

        .tm-karcis-main {
            min-width: 0;
        }

        .tm-karcis-title {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .tm-karcis-meta {
            color: var(--tm-muted);
            font-size: 10px;
            margin-top: 2px;
        }

        .tm-detail-header {
            align-items: center;
            background: linear-gradient(135deg, #102a43 0%, #0f766e 100%);
            color: #fff;
            display: flex;
            justify-content: space-between;
            padding: 18px 20px;
        }

        .tm-detail-header-main {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .tm-detail-header-icon {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border-radius: 8px;
            display: flex;
            font-size: 24px;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .tm-detail-metrics {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            margin-bottom: 12px;
        }

        .tm-detail-metric {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 11px;
        }

        .tm-detail-metric.grand {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }

        .tm-detail-metric span {
            color: var(--tm-muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .tm-detail-metric strong {
            color: #0f172a;
            font-size: 16px;
        }

        .tm-detail-metric.grand strong {
            color: #047857;
            font-size: 18px;
            font-weight: 900;
        }

        .tm-detail-section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .tm-detail-section-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px;
        }

        .tm-detail-section-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
        }

        .tm-transaction-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 420px;
        }

        .tm-transaction-toolbar .form-control,
        .tm-transaction-toolbar .form-select {
            font-size: 12px;
            min-height: 34px;
        }

        .tm-detail-clickable {
            cursor: pointer;
        }

        .tm-route-note {
            color: #0f766e;
            font-size: 11px;
            font-weight: 800;
            margin-top: 2px;
        }

        @media (max-width: 1199.98px) {
            .tm-hero,
            .tm-readiness-grid,
            .tm-summary-grid,
            .tm-detail-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tm-hero-main {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 767.98px) {
            .tm-hero,
            .tm-hero-controls,
            .tm-tabs,
            .tm-dependencies,
            .tm-source-grid,
            .tm-readiness-grid,
            .tm-summary-grid,
            .tm-config-grid,
            .tm-detail-metrics {
                grid-template-columns: 1fr;
            }

            .tm-panel-head,
            .tm-preview-head,
            .tm-detail-section-head {
                align-items: stretch;
                flex-direction: column;
            }

            .tm-panel-actions,
            .tm-preview-actions,
            .tm-transaction-toolbar {
                min-width: 0;
                width: 100%;
            }

            .tm-preview-actions .form-control,
            .tm-preview-actions .form-select,
            .tm-transaction-toolbar .form-control,
            .tm-transaction-toolbar .form-select {
                width: 100%;
            }

            .tm-source-rule {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section("content")
    <div class="tm-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a>
                </li>
                <li class="breadcrumb-item active">Tindakan Medis</li>
            </ol>
        </nav>

        <section class="tm-hero">
            <div class="tm-hero-main">
                <div class="tm-hero-icon">
                    <i class="mdi mdi-stethoscope"></i>
                </div>
                <div>
                    <div class="tm-eyebrow">Medical Premium Generator</div>
                    <h4 class="tm-title">Generate Premi Tindakan Medis</h4>
                    <p class="tm-description">
                        Hitung premi dari rawat jalan/inap dokter dan paramedis, gabungkan UGD/VK terpilih,
                        dan simpan snapshot rawat sebagai audit perhitungan.
                    </p>
                </div>
            </div>
            <div class="tm-hero-controls">
                <div class="tm-hero-field">
                    <label for="periodeTindakanMedis">Periode</label>
                    <input type="month" id="periodeTindakanMedis" class="form-control">
                </div>
                <div class="tm-hero-field">
                    <label>Konfigurasi Aktif</label>
                    <div class="tm-active-premi" id="activeConfigPremiTindakan">Memuat konfigurasi...</div>
                    <div class="tm-active-note" id="activeConfigPremiTindakanNote">-</div>
                </div>
            </div>
        </section>

        <div class="tm-tabs">
            <button type="button" class="tm-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>UMUM</strong>
                    <small>Sumber tindakan mengikuti periode generate</small>
                </span>
            </button>
            <button type="button" class="tm-tab" data-type="bpjs">
                <i class="mdi mdi-shield-account-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>Sumber tindakan mengikuti konfigurasi BPJS</small>
                </span>
            </button>
        </div>

        <section class="tm-panel">
            <div class="tm-panel-head">
                <div>
                    <div class="tm-panel-title">Kesiapan dan Preview</div>
                    <div class="tm-panel-subtitle" id="summaryTindakanMedisSubtitle">
                        Memeriksa data periode terpilih...
                    </div>
                </div>
                <div class="tm-panel-actions">
                    <button type="button" id="btnConfigTindakanMedis" class="tm-btn config">
                        <i class="mdi mdi-tune-variant"></i>
                        <span>Konfigurasi</span>
                    </button>
                    <button type="button" id="btnGenerateTindakanMedis" class="tm-btn generate" disabled>
                        <i class="mdi mdi-cog-play-outline"></i>
                        <span>Generate Premi</span>
                    </button>
                </div>
            </div>
            <div class="tm-body">
                <div class="tm-dependencies">
                    <div class="tm-dependency" id="dependencyUgdTindakan">
                        <div class="tm-dependency-icon">
                            <i class="mdi mdi-doctor"></i>
                        </div>
                        <div>
                            <div class="tm-dependency-label">Generate UGD</div>
                            <div class="tm-dependency-value" id="dependencyUgdValue">Memeriksa...</div>
                            <div class="tm-dependency-note" id="dependencyUgdNote">-</div>
                        </div>
                    </div>
                    <div class="tm-dependency" id="dependencyVkTindakan">
                        <div class="tm-dependency-icon">
                            <i class="mdi mdi-mother-nurse"></i>
                        </div>
                        <div>
                            <div class="tm-dependency-label">Generate VK</div>
                            <div class="tm-dependency-value" id="dependencyVkValue">Memeriksa...</div>
                            <div class="tm-dependency-note" id="dependencyVkNote">-</div>
                        </div>
                    </div>
                </div>

                <div class="tm-source-grid">
                    <div class="tm-source-field">
                        <label for="sourceUgdTindakanMedis">Sumber Data UGD</label>
                        <select id="sourceUgdTindakanMedis" class="form-select">
                            <option value="">Memuat sumber UGD...</option>
                        </select>
                        <small class="tm-source-note" id="sourceUgdTindakanMedisNote">
                            Pilih hasil UGD berdasarkan ploting premi.
                        </small>
                    </div>
                    <div class="tm-source-field">
                        <label for="sourceVkTindakanMedis">Sumber Data VK</label>
                        <select id="sourceVkTindakanMedis" class="form-select">
                            <option value="">Memuat sumber VK...</option>
                        </select>
                        <small class="tm-source-note" id="sourceVkTindakanMedisNote">
                            Pilih hasil VK berdasarkan ploting premi.
                        </small>
                    </div>
                </div>

                <div class="tm-readiness-grid" id="summaryReadinessStepsMedis">
                    <div class="tm-readiness-item">
                        <div class="tm-readiness-icon"><i class="mdi mdi-timer-sand"></i></div>
                        <div>
                            <div class="tm-readiness-label">Kesiapan</div>
                            <div class="tm-readiness-value">Memeriksa...</div>
                            <div class="tm-readiness-note">Pilih periode untuk melihat status.</div>
                        </div>
                    </div>
                </div>

                <div class="tm-info-strip" id="summaryPeriodInfoMedis">
                    <div class="tm-info-pill">
                        <i class="mdi mdi-calendar-month-outline"></i>
                        <span>Tindakan</span>
                        <strong>-</strong>
                    </div>
                    <div class="tm-info-pill">
                        <i class="mdi mdi-hospital-building"></i>
                        <span>UGD/VK</span>
                        <strong>-</strong>
                    </div>
                </div>

                <div class="tm-summary-grid">
                    <div class="tm-summary-card">
                        <div class="tm-summary-label">Transaksi Rawat</div>
                        <div class="tm-summary-value" id="summaryTransaksiMedis">0</div>
                        <div class="tm-summary-note" id="summaryPasienMedis">0 pasien</div>
                    </div>
                    <div class="tm-summary-card">
                        <div class="tm-summary-label">Biaya Rawat</div>
                        <div class="tm-summary-value" id="summaryBiayaRawatMedis">Rp 0</div>
                        <div class="tm-summary-note" id="summaryIgnoreMedis">0 ICU / 0 NICU diabaikan</div>
                    </div>
                    <div class="tm-summary-card primary">
                        <div class="tm-summary-label">Hasil Mapping</div>
                        <div class="tm-summary-value" id="summaryMappingMedis">Rp 0</div>
                        <div class="tm-summary-note" id="summaryJumlahMappingMedis">0 mapping</div>
                    </div>
                    <div class="tm-summary-card">
                        <div class="tm-summary-label">UGD + VK</div>
                        <div class="tm-summary-value" id="summaryUgdVkMedis">Rp 0</div>
                        <div class="tm-summary-note" id="summaryUgdVkNote">UGD Rp 0 / VK Rp 0</div>
                    </div>
                    <div class="tm-summary-card">
                        <div class="tm-summary-label">Pool ICU BPJS</div>
                        <div class="tm-summary-value" id="summaryIcuPoolBpjsMedis">Rp 0</div>
                        <div class="tm-summary-note" id="summaryIcuPoolBpjsNote">Khusus BPJS</div>
                    </div>
                    <div class="tm-summary-card total">
                        <div class="tm-summary-label">Grand Total</div>
                        <div class="tm-summary-value" id="summaryGrandMedis">Rp 0</div>
                        <div class="tm-summary-before strong" id="summaryFinalMedis">Setelah pembagi: Rp 0</div>
                        <div class="tm-summary-note" id="summaryPembagiMedis">Pembagi: 1</div>
                    </div>
                    <div class="tm-summary-card primary">
                        <div class="tm-summary-label">Dibagikan</div>
                        <div class="tm-summary-value" id="summaryDibagikanMedis">Rp 0</div>
                        <div class="tm-summary-before" id="summaryTambahanMedis">ICU Rp 0 / NICU Rp 0</div>
                        <div class="tm-summary-note" id="summaryDibagikanNote">0 penerima</div>
                    </div>
                </div>

                <div class="tm-formula">
                    <i class="mdi mdi-function-variant"></i>
                    <span id="summaryFormulaMedis">
                        Grand total sebelum pembagi: <strong>Rp 0</strong>
                        <span class="tm-formula-muted">(Rp 0 + Rp 0 + Rp 0 + Rp 0)</span> &rarr; / 1 = Rp 0
                    </span>
                </div>

                <div class="tm-preview mb-3">
                    <div class="tm-preview-head">
                        <div>
                            <div class="tm-preview-title">Preview Distribusi Pegawai</div>
                            <div class="tm-preview-note" id="summaryDistributionNoteMedis">
                                Grand total ditampilkan sebelum pembagi; nilai setelah pembagi dibagikan ke pegawai.
                            </div>
                        </div>
                        <div class="tm-preview-actions">
                            <input type="search" id="summaryDistributionSearchMedis" class="form-control"
                                placeholder="Cari pegawai">
                            <select id="summaryDistributionBonusFilterMedis" class="form-select">
                                <option value="all">Semua penerima</option>
                                <option value="bonus">Ada ICU/NICU</option>
                                <option value="no_bonus">Tanpa ICU/NICU</option>
                            </select>
                            <span class="tm-badge umum" id="summaryDistributionCountMedis">0 penerima</span>
                        </div>
                    </div>
                    <div class="tm-insight-strip" id="summaryDistributionInsightMedis"></div>
                    <div class="table-responsive">
                        <table class="table table-hover tm-preview-table">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th class="text-end">Dasar</th>
                                    <th class="text-end">ICU</th>
                                    <th class="text-end">NICU</th>
                                    <th class="text-end">Total Diterima</th>
                                </tr>
                            </thead>
                            <tbody id="summaryDistributionRowsMedis">
                                <tr>
                                    <td colspan="5" class="tm-empty">Distribusi belum tersedia.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tm-preview">
                    <div class="tm-preview-head">
                        <div>
                            <div class="tm-preview-title">Preview Per Tindakan</div>
                            <div class="tm-preview-note">
                                Nilai sebelum dan sesudah olah mengikuti jenis persen atau nominal.
                            </div>
                        </div>
                        <div class="tm-preview-actions">
                            <input type="search" id="summaryPreviewSearchMedis" class="form-control"
                                placeholder="Cari tindakan">
                            <select id="summaryPreviewSourceFilterMedis" class="form-select">
                                <option value="all">Semua sumber</option>
                                <option value="doctor">Rawat dokter</option>
                                <option value="paramedic">Rawat paramedis</option>
                                <option value="drpr">Dokter & paramedis</option>
                                <option value="routed">Dialihkan ke perawat</option>
                                <option value="karcis">Karcis BPJS</option>
                            </select>
                            <span class="tm-badge umum" id="summaryPreviewCountMedis">0 tindakan</span>
                        </div>
                    </div>
                    <div class="tm-insight-strip" id="summaryPreviewInsightMedis"></div>
                    <div class="table-responsive">
                        <table class="table table-hover tm-preview-table">
                            <thead>
                                <tr>
                                    <th>Tindakan</th>
                                    <th>Sumber</th>
                                    <th class="text-center">Data</th>
                                    <th class="text-end">Sebelum Diolah</th>
                                    <th class="text-end">Nilai</th>
                                    <th class="text-end">Sesudah Diolah</th>
                                </tr>
                            </thead>
                            <tbody id="summaryPreviewRowsMedis">
                                <tr>
                                    <td colspan="6" class="tm-empty">Preview belum tersedia.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="tm-panel">
            <div class="tm-panel-head">
                <div>
                    <div class="tm-panel-title">Riwayat Generate</div>
                    <div class="tm-panel-subtitle">Hasil tersimpan per periode, jenis pelayanan, dan mapping premi.</div>
                </div>
                <span class="tm-badge umum" id="activeTypeBadgeMedis">UMUM</span>
            </div>
            <div class="tm-body">
                <div class="table-responsive">
                    <table id="tableGenerateTindakanMedis" class="table table-hover tm-table w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jenis</th>
                                <th>Premi</th>
                                <th class="text-center">Transaksi</th>
                                <th class="text-end">Mapping</th>
                                <th class="text-end">UGD</th>
                                <th class="text-end">VK</th>
                                <th class="text-end">Pool ICU BPJS</th>
                                <th class="text-end">Grand Total</th>
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

        <div class="modal fade" id="modalTindakanMedisConfig" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-sm">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold">Konfigurasi Tindakan Medis</h5>
                            <small class="text-muted">
                                Pilih sumber mapping UMUM dan BPJS, lalu atur aturan tindakan yang mengikat data rawat.
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="tm-config-grid mb-3">
                            <div>
                                <label for="configMappingPremiUmumTindakan" class="form-label fw-semibold">
                                    Mapping Premi UMUM
                                </label>
                                <select id="configMappingPremiUmumTindakan" class="form-select">
                                    <option value="">Memuat mapping premi...</option>
                                </select>
                                <small class="text-muted" id="configMappingPremiUmumTindakanNote">-</small>
                            </div>
                            <div>
                                <label for="configMappingPremiBpjsTindakan" class="form-label fw-semibold">
                                    Mapping Premi BPJS
                                </label>
                                <select id="configMappingPremiBpjsTindakan" class="form-select">
                                    <option value="">Memuat mapping premi...</option>
                                </select>
                                <small class="text-muted" id="configMappingPremiBpjsTindakanNote">-</small>
                            </div>
                        </div>

                        <div class="tm-config-grid mb-3">
                            <div class="tm-config-box">
                                <div class="fw-semibold">Sumber Aktif di Halaman</div>
                                <small class="text-muted d-block mt-1" id="configActiveTypeNote">
                                    Aturan sumber memakai gabungan tindakan dari mapping UMUM dan BPJS.
                                </small>
                            </div>
                            <div>
                                <label for="configBpjsSourceModeTindakan" class="form-label fw-semibold">
                                    Periode Sumber BPJS
                                </label>
                                <select id="configBpjsSourceModeTindakan" class="form-select">
                                    <option value="previous">Bulan Sebelumnya</option>
                                    <option value="current">Samakan dengan Periode</option>
                                </select>
                                <small class="text-muted" id="configBpjsSourceModeNote">-</small>
                            </div>
                            <div>
                                <label for="configDistributionModeTindakan" class="form-label fw-semibold">
                                    Distribusi Pegawai
                                </label>
                                <select id="configDistributionModeTindakan" class="form-select">
                                    <option value="split_evenly">Dibagi rata ke pegawai</option>
                                    <option value="full_amount">Nilai final penuh per pegawai</option>
                                </select>
                                <small class="text-muted" id="configDistributionModeNote">
                                    Atur cara total final diberikan ke pegawai mapping premi.
                                </small>
                            </div>
                        </div>

                        <div class="tm-config-grid mb-3">
                            <label class="tm-config-box mb-0">
                                <input type="checkbox" class="form-check-input me-2" id="configIgnoreIcuTindakan">
                                <span class="fw-semibold">Abaikan tindakan dalam rentang ICU</span>
                                <small class="text-muted d-block mt-1">Mengikuti kamar dengan kd_bangsal ICU.</small>
                            </label>
                            <label class="tm-config-box mb-0">
                                <input type="checkbox" class="form-check-input me-2" id="configIgnoreNicuTindakan">
                                <span class="fw-semibold">Abaikan tindakan dalam rentang NICU</span>
                                <small class="text-muted d-block mt-1">Mengikuti kamar dengan kelas NICU.</small>
                            </label>
                        </div>

                        <div class="tm-config-grid mb-3">
                            <label class="tm-config-box mb-0">
                                <input type="checkbox" class="form-check-input me-2" id="configIncludeBpjsIcuPoolTindakan">
                                <span class="fw-semibold">Tambahkan pool premi medis ICU BPJS</span>
                                <small class="text-muted d-block mt-1">
                                    Saat generate BPJS, grand total ditambah total_premi_medis_pool dari ICU BPJS terkunci.
                                </small>
                            </label>
                            <label class="tm-config-box mb-0">
                                <input type="checkbox" class="form-check-input me-2" id="configBpjsIgnoreUgdTindakan">
                                <span class="fw-semibold">BPJS abaikan UGD</span>
                                <small class="text-muted d-block mt-1">Total UGD BPJS dibuat 0 dan tidak wajib dipilih.</small>
                            </label>
                            <label class="tm-config-box mb-0">
                                <input type="checkbox" class="form-check-input me-2" id="configBpjsIgnoreVkTindakan">
                                <span class="fw-semibold">BPJS abaikan VK</span>
                                <small class="text-muted d-block mt-1">Total VK BPJS dibuat 0 dan tidak wajib dipilih.</small>
                            </label>
                        </div>

                        <div class="tm-config-box mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">Filter Dokter Rawat Dokter</div>
                                    <small class="text-muted">
                                        Dokter terpilih masuk tindakan dokter; dokter lain dapat masuk tindakan perawat.
                                    </small>
                                </div>
                                <span class="badge bg-light text-dark" id="configDoctorCountTindakan">
                                    Semua dokter
                                </span>
                            </div>
                            <select id="configDoctorFilterTindakan" class="form-select" multiple="multiple"
                                style="width: 100%;"></select>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-3 mb-2">
                                <div class="fw-semibold">Tindakan yang Difilter Dokter</div>
                                <span class="badge bg-light text-dark" id="configDoctorActionCountTindakan">
                                    0 tindakan dipilih
                                </span>
                            </div>
                            <div class="tm-karcis-list" id="configDoctorActionListTindakan">
                                <div class="tm-empty">Pilih mapping premi terlebih dahulu.</div>
                            </div>
                        </div>

                        <div class="tm-config-box mb-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">Tindakan Karcis BPJS</div>
                                    <small class="text-muted">
                                        Dikeluarkan dari Generate BPJS, lalu ditambahkan ke Generate UMUM memakai nilai BPJS.
                                    </small>
                                </div>
                                <span class="badge bg-light text-dark" id="karcisConfigSelectedCountTindakan">
                                    0 tindakan dipilih
                                </span>
                            </div>
                            <input type="search" id="karcisConfigSearchTindakan" class="form-control mb-2"
                                placeholder="Cari kode atau nama tindakan karcis">
                            <div class="tm-karcis-list" id="karcisConfigListTindakan">
                                <div class="tm-empty">Memuat tindakan...</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-semibold">Mapping Sumber Data ke Tindakan</div>
                                <small class="text-muted" id="sourceRuleMeta">
                                    0 aturan sumber
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddSourceRule">
                                <i class="mdi mdi-plus me-1"></i>
                                Tambah Aturan
                            </button>
                        </div>
                        <div id="sourceRuleList">
                            <div class="tm-empty border rounded">Belum ada aturan sumber.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="btnSaveConfigTindakanMedis">
                            <i class="mdi mdi-content-save-outline me-1"></i>
                            Simpan Konfigurasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @include("simrs.backOffice.keuangan.hitungPremi.generateTindakanMedis.modal")
    </div>
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateTindakanMedis.jsMain")
@endpush
