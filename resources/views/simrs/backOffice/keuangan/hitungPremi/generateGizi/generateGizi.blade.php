@extends("template.partials.app")

@push("style")
    @include("template.AddOn.dataTables")
    @include("template.AddOn.select2")
    @include("template.AddOn.mdiicon")
    @include("template.AddOn.sweetAlert")

    <style>
        .gizi-page {
            --gizi-main: #15803d;
            --gizi-deep: #0f172a;
            --gizi-blue: #2563eb;
            --gizi-rose: #e11d48;
            --gizi-amber: #d97706;
            --gizi-ink: #172033;
            --gizi-muted: #64748b;
            --gizi-line: #e2e8f0;
            --gizi-soft: #f0fdf4;
            color: var(--gizi-ink);
        }

        .gizi-page .btn {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-weight: 700;
            gap: 5px;
            justify-content: center;
        }

        .gizi-page .breadcrumb {
            background: transparent;
            font-size: 13px;
            margin-bottom: 0;
            padding: 0;
        }

        .gizi-hero {
            align-items: stretch;
            background: linear-gradient(135deg, #fff 0%, #f0fdf4 44%, #eff6ff 100%);
            border: 1px solid var(--gizi-line);
            border-left: 5px solid var(--gizi-main);
            border-radius: 8px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .08);
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-top: 14px;
            padding: 18px;
        }

        .gizi-hero-main,
        .gizi-inline {
            align-items: center;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .gizi-hero-main {
            align-items: flex-start;
        }

        .gizi-hero-icon,
        .gizi-simple-icon {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            justify-content: center;
        }

        .gizi-hero-icon {
            background: #dcfce7;
            color: var(--gizi-main);
            flex: 0 0 auto;
            font-size: 30px;
            height: 54px;
            width: 54px;
        }

        .gizi-eyebrow {
            color: var(--gizi-main);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .gizi-title {
            color: var(--gizi-deep);
            font-size: 23px;
            font-weight: 850;
            margin: 3px 0 5px;
        }

        .gizi-description {
            color: var(--gizi-muted);
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
            max-width: 760px;
        }

        .gizi-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 12px;
        }

        .gizi-chip {
            align-items: center;
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            min-height: 30px;
            padding: 6px 10px;
        }

        .gizi-chip i {
            color: var(--gizi-main);
            font-size: 15px;
        }

        .gizi-chip strong {
            color: var(--gizi-deep);
            font-weight: 850;
        }

        .gizi-hero-controls {
            display: grid;
            flex: 0 0 320px;
            gap: 10px;
        }

        .gizi-control-box,
        .gizi-panel,
        .gizi-simple-section,
        .gizi-summary-item,
        .gizi-recipient-panel {
            background: #fff;
            border: 1px solid var(--gizi-line);
            border-radius: 8px;
        }

        .gizi-control-box {
            background: #f8fafc;
            padding: 11px;
        }

        .gizi-control-box label {
            color: #475569;
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .gizi-period-picker {
            align-items: center;
            display: grid;
            gap: 7px;
            grid-template-columns: 38px minmax(0, 1fr) 38px;
        }

        .gizi-period-picker .form-control {
            height: 38px;
            text-align: center;
        }

        .gizi-period-step {
            font-size: 17px;
            height: 38px;
            padding: 0;
        }

        .gizi-type-tabs {
            background: #fff;
            border: 1px solid var(--gizi-line);
            border-radius: 8px;
            display: flex;
            gap: 8px;
            margin: 15px 0;
            padding: 8px;
        }

        .gizi-type-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            flex: 1;
            gap: 10px;
            padding: 10px 12px;
            text-align: left;
            transition: background-color .18s ease, box-shadow .18s ease, color .18s ease;
        }

        .gizi-type-tab i {
            font-size: 22px;
        }

        .gizi-type-tab strong,
        .gizi-type-tab small {
            display: block;
        }

        .gizi-type-tab small {
            font-size: 11px;
        }

        .gizi-type-tab:hover {
            background: #f8fafc;
            color: #334155;
        }

        .gizi-type-tab.active {
            background: #ecfdf5;
            box-shadow: inset 0 0 0 1px #bbf7d0;
            color: #166534;
        }

        .gizi-panel {
            box-shadow: 0 14px 30px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .gizi-panel-head,
        .gizi-filter-bar {
            align-items: center;
            border-bottom: 1px solid var(--gizi-line);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 16px;
        }

        .gizi-panel-title {
            color: var(--gizi-deep);
            font-size: 16px;
            font-weight: 850;
        }

        .gizi-panel-subtitle {
            color: var(--gizi-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .gizi-command-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .gizi-live-strip {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 16px 16px 0;
        }

        .gizi-live-item {
            background: #f8fafc;
            border: 1px solid var(--gizi-line);
            border-radius: 8px;
            padding: 12px;
        }

        .gizi-live-label,
        .gizi-summary-label,
        .gizi-detail-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .gizi-live-value {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 14px;
            font-weight: 850;
            gap: 8px;
            margin-top: 7px;
            min-width: 0;
        }

        .gizi-live-value i {
            color: var(--gizi-main);
            font-size: 18px;
        }

        .gizi-summary-board {
            padding: 16px;
        }

        .gizi-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .gizi-summary-item {
            min-height: 118px;
            padding: 14px;
            position: relative;
        }

        .gizi-summary-item::before {
            background: var(--item-color, var(--gizi-main));
            border-radius: 8px 8px 0 0;
            content: "";
            height: 4px;
            left: -1px;
            position: absolute;
            right: -1px;
            top: -1px;
        }

        .gizi-summary-value {
            color: var(--gizi-deep);
            font-size: 22px;
            font-weight: 850;
            line-height: 1.15;
            margin-top: 8px;
            overflow-wrap: anywhere;
        }

        .gizi-summary-note,
        .gizi-info-note {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 6px;
        }

        .gizi-distribution {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 12px;
        }

        .gizi-flow-card {
            background: #f8fafc;
            border: 1px solid var(--gizi-line);
            border-radius: 8px;
            padding: 13px;
        }

        .gizi-flow-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .gizi-flow-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
        }

        .gizi-flow-money {
            color: #0f172a;
            font-size: 15px;
            font-weight: 850;
            text-align: right;
        }

        .gizi-bar-track {
            background: #e2e8f0;
            border-radius: 999px;
            height: 7px;
            margin-top: 10px;
            overflow: hidden;
        }

        .gizi-bar-fill {
            background: var(--bar-color, var(--gizi-main));
            border-radius: 999px;
            height: 100%;
            max-width: 100%;
        }

        .gizi-table-wrap {
            padding: 14px 16px 16px;
        }

        .gizi-table-wrap table {
            font-size: 12px;
        }

        .gizi-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
        }

        .gizi-badge {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 5px;
            padding: 5px 9px;
            white-space: nowrap;
        }

        .gizi-badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .gizi-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .gizi-badge.info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .gizi-badge.danger {
            background: #ffe4e6;
            color: #be123c;
        }

        .gizi-modal-content {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .gizi-modal-header {
            background: #f8fafc;
            border-bottom: 1px solid var(--gizi-line);
        }

        .gizi-simple-icon {
            background: #dcfce7;
            color: var(--gizi-main);
            flex: 0 0 auto;
            font-size: 23px;
            height: 42px;
            width: 42px;
        }

        .gizi-simple-head {
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--gizi-line);
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 13px;
        }

        .gizi-simple-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 850;
        }

        .gizi-simple-text {
            color: var(--gizi-muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .gizi-simple-badge {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            color: #166534;
            font-size: 11px;
            font-weight: 850;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .gizi-state-banner {
            align-items: flex-start;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            padding: 12px;
        }

        .gizi-state-banner i {
            font-size: 22px;
            margin-top: 1px;
        }

        .gizi-state-banner.neutral {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        .gizi-state-banner.success {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .gizi-state-banner.warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .gizi-state-banner.danger {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
        }

        .gizi-state-title {
            font-size: 13px;
            font-weight: 850;
        }

        .gizi-state-text {
            font-size: 12px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .gizi-simple-section {
            padding: 13px;
        }

        .gizi-simple-section-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 850;
            gap: 7px;
            margin-bottom: 10px;
        }

        .gizi-preview-grid,
        .gizi-detail-kpi,
        .gizi-config-grid,
        .gizi-recipient-grid {
            display: grid;
            gap: 10px;
        }

        .gizi-preview-grid,
        .gizi-detail-kpi {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .gizi-config-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .gizi-recipient-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .gizi-detail-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 850;
            margin-top: 6px;
            overflow-wrap: anywhere;
        }

        .gizi-info-list {
            display: grid;
            gap: 8px;
        }

        .gizi-info-row {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 10px;
        }

        .gizi-info-row strong {
            color: #0f172a;
            font-size: 13px;
        }

        .gizi-info-row span {
            color: #64748b;
            font-size: 12px;
            text-align: right;
        }

        .gizi-detail-table {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            max-height: 360px;
            overflow: auto;
        }

        .gizi-detail-table table {
            font-size: 12px;
            margin-bottom: 0;
        }

        .gizi-detail-meta {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .gizi-detail-meta-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 0;
            padding: 10px;
        }

        .gizi-detail-meta-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
            margin-top: 4px;
            overflow-wrap: anywhere;
        }

        .gizi-detail-tabs {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 7px;
            margin-bottom: 12px;
            padding: 7px;
        }

        .gizi-detail-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #64748b;
            display: inline-flex;
            flex: 1;
            font-size: 12px;
            font-weight: 850;
            gap: 7px;
            justify-content: center;
            min-height: 38px;
            padding: 8px 10px;
        }

        .gizi-detail-tab.active {
            background: #dcfce7;
            box-shadow: inset 0 0 0 1px #bbf7d0;
            color: #166534;
        }

        .gizi-detail-panel {
            display: none;
        }

        .gizi-detail-panel.active {
            display: block;
        }

        .gizi-detail-filterbar {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) 190px auto auto;
            margin-bottom: 12px;
            padding: 10px;
        }

        .gizi-segmented {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 5px;
            padding: 4px;
        }

        .gizi-segmented button {
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: #64748b;
            font-size: 12px;
            font-weight: 850;
            min-height: 32px;
            padding: 6px 10px;
        }

        .gizi-segmented button.active {
            background: #ecfdf5;
            color: #166534;
        }

        .gizi-detail-filterbar .form-control,
        .gizi-detail-filterbar .form-select {
            border-radius: 8px;
            font-size: 12px;
            min-height: 38px;
        }

        .gizi-detail-count {
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .gizi-recipient-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        .gizi-recipient-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .gizi-recipient-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
        }

        .gizi-recipient-total {
            color: #15803d;
            font-size: 14px;
            font-weight: 850;
            text-align: right;
        }

        .gizi-recipient-list {
            display: grid;
            gap: 8px;
        }

        .gizi-recipient-person {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            padding: 9px;
        }

        .gizi-recipient-avatar {
            align-items: center;
            background: #dcfce7;
            border-radius: 8px;
            color: #166534;
            display: inline-flex;
            font-size: 17px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .gizi-recipient-name {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
        }

        .gizi-recipient-note {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .gizi-recipient-money {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
            text-align: right;
            white-space: nowrap;
        }

        .gizi-empty-state {
            align-items: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #64748b;
            display: flex;
            gap: 10px;
            padding: 13px;
        }

        .gizi-empty-state i {
            color: var(--gizi-main);
            font-size: 24px;
        }

        .select2-container--default .select2-selection--multiple {
            border-color: #d1d5db;
            border-radius: 8px;
            min-height: 38px;
        }

        @media (max-width: 1199.98px) {
            .gizi-live-strip,
            .gizi-summary-grid,
            .gizi-preview-grid,
            .gizi-detail-kpi {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .gizi-hero {
                flex-direction: column;
            }

            .gizi-hero-controls {
                flex: initial;
            }

            .gizi-config-grid,
            .gizi-recipient-grid,
            .gizi-distribution,
            .gizi-detail-filterbar {
                grid-template-columns: 1fr;
            }

            .gizi-detail-meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .gizi-panel-head,
            .gizi-filter-bar,
            .gizi-simple-head {
                align-items: stretch;
                flex-direction: column;
            }

            .gizi-type-tabs,
            .gizi-live-strip,
            .gizi-summary-grid,
            .gizi-preview-grid,
            .gizi-detail-kpi {
                grid-template-columns: 1fr;
            }

            .gizi-detail-meta {
                grid-template-columns: 1fr;
            }

            .gizi-type-tabs {
                display: grid;
            }

            .gizi-detail-tabs,
            .gizi-segmented {
                display: grid;
            }

            .gizi-detail-count {
                text-align: left;
            }

            .gizi-recipient-person {
                grid-template-columns: 34px minmax(0, 1fr);
            }

            .gizi-recipient-money {
                grid-column: 1 / -1;
                text-align: left;
            }

            .gizi-command-row {
                justify-content: stretch;
            }

            .gizi-command-row .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section("content")
    <div class="gizi-page">
        <nav class="page-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route("backOffice.keuangan.hitungPremi") }}">Generate Premi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Generate Gizi</li>
            </ol>
        </nav>

        <section class="gizi-hero">
            <div class="gizi-hero-main">
                <div class="gizi-hero-icon"><i class="mdi mdi-food-apple-outline"></i></div>
                <div>
                    <div class="gizi-eyebrow">Nutrition Premium Generator</div>
                    <h4 class="gizi-title">Generate Premi Gizi</h4>
                    <p class="gizi-description">
                        Ambil tindakan rawat jalan dan rawat inap dari enam tabel sumber, cocokkan ke mapping Konsul atau
                        Diit, lalu hitung pembagian UMUM/BPJS dengan formula yang bisa dikonfigurasi.
                    </p>
                    <div class="gizi-hero-meta">
                        <span class="gizi-chip">
                            <i class="mdi mdi-account-cash-outline"></i>
                            Mode <strong id="heroGiziType">Umum</strong>
                        </span>
                        <span class="gizi-chip">
                            <i class="mdi mdi-calendar-sync-outline"></i>
                            Sumber <strong id="heroGiziSource">-</strong>
                        </span>
                        <span class="gizi-chip">
                            <i class="mdi mdi-database-check-outline"></i>
                            Status <strong id="heroGiziStatus">Memuat</strong>
                        </span>
                    </div>
                </div>
            </div>
            <div class="gizi-hero-controls">
                <div class="gizi-control-box">
                    <label>Periode Generate</label>
                    <div class="gizi-period-picker">
                        <button type="button" class="btn btn-light gizi-period-step" id="btnPrevGizi" title="Periode sebelumnya">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <input type="month" class="form-control" id="periodeGizi">
                        <button type="button" class="btn btn-light gizi-period-step" id="btnNextGizi" title="Periode berikutnya">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="gizi-type-tabs">
            <button type="button" class="gizi-type-tab active" data-type="umum">
                <i class="mdi mdi-account-cash-outline"></i>
                <span>
                    <strong>Umum</strong>
                    <small>kd_pj selain BPJ dan -</small>
                </span>
            </button>
            <button type="button" class="gizi-type-tab" data-type="bpjs">
                <i class="mdi mdi-shield-check-outline"></i>
                <span>
                    <strong>BPJS</strong>
                    <small>kd_pj sama dengan BPJ</small>
                </span>
            </button>
        </div>

        <section class="gizi-panel">
            <div class="gizi-panel-head">
                <div>
                    <div class="gizi-panel-title">Ringkasan Periode</div>
                    <div class="gizi-panel-subtitle" id="giziSourceRule">Memuat aturan sumber data...</div>
                </div>
                <div class="gizi-command-row">
                    <button type="button" class="btn btn-outline-secondary" id="btnConfigGizi">
                        <i class="mdi mdi-tune-variant"></i> Konfigurasi
                    </button>
                    <button type="button" class="btn btn-primary" id="btnPreviewGizi">
                        <i class="mdi mdi-play-circle-outline"></i> Preview Generate
                    </button>
                </div>
            </div>
            <div class="gizi-live-strip" id="giziLiveStrip"></div>
            <div class="gizi-summary-board">
                <div class="gizi-summary-grid" id="summaryGiziGrid"></div>
                <div class="gizi-distribution" id="summaryGiziDistribution"></div>
            </div>
        </section>

        <section class="gizi-panel">
            <div class="gizi-filter-bar">
                <div>
                    <div class="gizi-panel-title">Riwayat Generate Gizi</div>
                    <div class="gizi-panel-subtitle">Hasil generate disimpan bersama detail tindakan sumber.</div>
                </div>
                <button type="button" class="btn btn-light" id="btnRefreshGizi" title="Refresh">
                    <i class="mdi mdi-refresh"></i>
                </button>
            </div>
            <div class="gizi-table-wrap">
                <table class="table table-hover table-striped align-middle w-100" id="tableGenerateGizi">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Jenis</th>
                            <th>Sumber</th>
                            <th>Mapping</th>
                            <th>Pasien</th>
                            <th>Tindakan</th>
                            <th>Grand Total</th>
                            <th>Konsul</th>
                            <th>Diit</th>
                            <th>Bersama</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>
    </div>

    @include("simrs.backOffice.keuangan.hitungPremi.generateGizi.modal")
@endsection

@push("scripts")
    @include("simrs.backOffice.keuangan.hitungPremi.generateGizi.jsMain")
@endpush
